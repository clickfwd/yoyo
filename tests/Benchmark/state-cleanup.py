import argparse
import hashlib
import itertools
import json
import os
from pathlib import Path
import platform
import statistics
import subprocess
import tempfile


def source_digest(root):
    digest = hashlib.sha256()
    files = sorted((root / "src").rglob("*.php"))
    if not files or not (root / "src/yoyo/Yoyo.php").is_file():
        raise ValueError(f"Not a Yoyo source tree: {root}")
    for path in files:
        digest.update(path.relative_to(root).as_posix().encode() + b"\0")
        digest.update(path.read_bytes() + b"\0")
    return digest.hexdigest()


def rank_sum_probability(first, second):
    pooled = first + second
    ranks = [sum(other < value for other in pooled) + (sum(other == value for other in pooled) + 1) / 2 for value in pooled]
    center = len(first) * (len(pooled) + 1) / 2
    observed = abs(sum(ranks[:len(first)]) - center)
    sums = [abs(sum(subset) - center) for subset in itertools.combinations(ranks, len(first))]
    return sum(value >= observed for value in sums) / len(sums)


parser = argparse.ArgumentParser(description="Compare isolated initial native renders with prebuilt rows and cold reflection metadata.")
parser.add_argument("baseline", type=Path, help="Baseline checkout or extracted source tree")
parser.add_argument("candidate", type=Path, help="Candidate checkout or extracted source tree")
parser.add_argument("--vendor", type=Path, help="Installed vendor directory; defaults to candidate/vendor")
parser.add_argument("--php", default="php")
parser.add_argument("--trials", type=int, default=5)
parser.add_argument("--iterations", type=int, default=300)
args = parser.parse_args()
if not 5 <= args.trials <= 10 or args.iterations < 1:
    parser.error("Use 5 to 10 alternating pairs and at least one timed iteration.")
sources = {"baseline": args.baseline.resolve(), "candidate": args.candidate.resolve()}
vendor = (args.vendor or sources["candidate"] / "vendor").resolve()
if not (vendor / "psr/container/src/ContainerInterface.php").is_file():
    parser.error("The vendor directory must contain the installed psr/container interfaces.")
digests = {side: source_digest(root) for side, root in sources.items()}
flags = ["-d", "opcache.enable_cli=1", "-d", "opcache.jit=disable", "-d", "error_reporting=-1"]
report = {
    "context": {"platform": platform.platform(), "logical_cpus": os.cpu_count(),
                "load_before": os.getloadavg(), "flags": flags, "warmups": 10,
                "iterations_per_trial": args.iterations, "trials_per_side": args.trials,
                "concurrency": 1, "source_sha256": digests,
                "psr_container_sha256": hashlib.sha256((vendor / "psr/container/src/ContainerInterface.php").read_bytes()).hexdigest()},
    "trials": [], "summary": [],
}
worker = Path(__file__).with_name("state-cleanup-worker.php")
with tempfile.TemporaryDirectory(prefix="yoyo-state-cleanup-") as temporary:
    for workload, row_count in [("discarded", 50), ("exposed", 50), ("empty", 0), ("small", 1)]:
        reference = None
        for trial in range(args.trials):
            for side, source in sources.items():
                output = Path(temporary) / f"{side}.html"
                process = subprocess.run([args.php, *flags, str(worker), str(source), str(vendor), workload,
                                          str(args.iterations), str(output)], capture_output=True, text=True)
                if process.returncode or process.stderr:
                    raise RuntimeError(f"{workload}/{side} failed with exit {process.returncode}:\n{process.stdout}\n{process.stderr}")
                record = json.loads(process.stdout)
                html = output.read_bytes()
                if reference is None:
                    reference = html
                if html != reference:
                    raise RuntimeError(f"{workload}/{side} produced different HTML bytes.")
                expected = {"errors": 0, "validated_renders": args.iterations, "status": 200, "headers": [],
                            "rows_rendered": args.iterations * row_count, "tags_rendered": args.iterations * row_count * 8,
                            "choose_controls_rendered": args.iterations * row_count, "page_controls_rendered": args.iterations,
                            "dispatched_actions": 0, "output_bytes": len(html) * args.iterations,
                            "output_sha256": hashlib.sha256(html).hexdigest(),
                            "renderer_sha256": hashlib.sha256((source / "src/yoyo/Yoyo.php").read_bytes()).hexdigest()}
                if any(record[key] != value for key, value in expected.items()) or source_digest(source) != digests[side]:
                    raise RuntimeError(f"{workload}/{side} source, work, response, or output validation failed.")
                record.update(side=side, workload=workload, trial=trial, load=os.getloadavg())
                report["trials"].append(record)
        summary = {"workload": workload}
        samples = {}
        for side in sources:
            group = [row for row in report["trials"] if row["workload"] == workload and row["side"] == side]
            samples[side] = [row["wall_us_per_render"] for row in group]
            summary[side] = {}
            for metric in ["wall_us_per_render", "cpu_us_per_render"]:
                values = [row[metric] for row in group]
                summary[side][metric] = {"median": statistics.median(values), "min": min(values), "max": max(values)}
        summary["wall_reduction_percent"] = 100 * (1 - statistics.median(samples["candidate"]) / statistics.median(samples["baseline"]))
        summary["wall_rank_sum_two_sided_p"] = rank_sum_probability(samples["baseline"], samples["candidate"])
        report["summary"].append(summary)
report["context"]["load_after"] = os.getloadavg()
print(json.dumps(report, indent=2))
