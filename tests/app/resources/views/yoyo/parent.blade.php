<div yoyo:props="data">
@foreach ($data as $id)
    
    @yoyo('child', ['id' => $id], ['id'=>'child-'.$id])

@endforeach
</div>