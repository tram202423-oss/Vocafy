@extends('layouts.app')

@section('content')

{{-- Gọi component và truyền dữ liệu động vào --}}
<x-page-header 
    :title="$topic->name"
    badge="Danh mục chủ đề"
    :description="$topic->description"
    :breadcrumbs="[$category->name => route('categories.show', $category->slug), $topic->name => '']" 
/>


@endsection