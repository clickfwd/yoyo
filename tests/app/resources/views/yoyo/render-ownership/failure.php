<?php

echo "PARTIAL";
ob_start();
echo "INNER";
$this->fail();
