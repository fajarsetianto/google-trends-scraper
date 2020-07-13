@extends('pages.layout')
@section('title','GTrend - Home')
@section('css')
<link href="{{asset('vendor/bootstrap-tags-input/tagsinput.css')}}" rel="stylesheet">
<link href="{{asset('custom/custom.css')}}" rel="stylesheet">
@endsection

@section('js')
    <script type="text/javascript" src="{{asset('vendor/bloodhound/bloodhound.js')}}"></script>  
    <script type="text/javascript" src="{{asset('vendor/bootstrap-tags-input/tagsinput.js')}}"></script>
    <script type="text/javascript" src="{{asset('custom/custom.js')}}"></script>
    <script>
        //    $(document).ready(function(){
                var data = {!!json_encode($categories)!!}
                $('.category-input').customSelect({
                    dataOriginal : data
                });
            $('.form-input-styled').uniform({
                fileButtonClass: 'action btn bg-info-400'
            });
                var suggestions = new Bloodhound({
                    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                    remote: {
                        url: '{{route("suggestion")}}?keyword=' + '%QUERY',
                        wildcard: '%QUERY',
                        filter: function(data){
                            return data.default.topics.map(function(value){
                                return value.title
                            })
                        }
                    },
                    
                });
                suggestions.initialize();

                var elt = $('#input-tags').tagsinput({
                    typeaheadjs: {
                        
                        source: suggestions.ttAdapter()
                    }
                });

                $('body .bootstrap-tagsinput input').on('keypress', function(e){
                    if(e.keyCode == 13){
                        e.preventDefault();
                    }
                });
            // })
    </script>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center" style="min-height: 100vh">
        <div class="w-100">
            <div class="row">
                <div class="col-md-5 col-12"></div>
                <div class="col-md-7 col-12">
                    <div class="row no-gutters">
                        <div class="col-md-3 d-flex align-items-center text-center bg-info-400">
                            <div>
                                <img src="{{asset('images/logo.png')}}" alt="" class="img-fluid">
                                <h3>G-Trend Corellation</h3>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="card rounded-0 mb-0">
                                <div class="card-body">
                                    @if(session('error'))
                                        <div class="alert alert-danger">
                                            {{ session('error')}}
                                        </div>
                                    @endif
                                    <form action="{{route('search')}}" enctype="multipart/form-data" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label>Keywords</label>
                                            <div class="d-block">
                                                <input type="text" placeholder="Type Keywords" id="input-tags" name="keyword" value="{{old('keyword') ? implode(',',old('keyword')) : ''}}" required/>  
                                            </div>
                                            @error('keyword')
                                                <span class="text-danger" role="alert">
                                                    <small>{{ $message }}</small>
                                                </span>
                                            @enderror
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Category</label>
                                            <input type="number" value="{{old('kategori') ? old('kategori') : 0}}" name="kategori" class="form-control category-input" required>
                                            @error('kategori')
                                                <span class="text-danger" role="alert">
                                                    <small>{{ $message }}</small>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label>Dataset</label>
                                            <input type="file" name="dataset" class="form-input-styled" required data-fouc accept=".xls,.xlsx">
                                            <span class="form-text text-muted">Accepted formats: xls, xlsx, csv. Max file size 2Mb or <a download href="{{asset('files/covid-19.xlsx')}}">Download the example dataset</a></span>
                                            @error('dataset*')
                                                <div class="">
                                                    <span class="text-danger" role="alert">
                                                        <small>{{ $message }}</small>
                                                    </span>
                                                </div>
                                            @enderror
                                        </div>
                                        <button type="submit" class="btn btn-info">Submit</button>
                                    </form>
                                </div>
                            </div>
                             
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
            
        
    </div>
</div>

    
@endsection

