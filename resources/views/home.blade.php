<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="csrf-token" content="{{csrf_token()}}">
      <script> window.Laravel= { crfsToken: '{{csrf_token()}}'}</script>
      <title>GTrends</title>
      <!-- Fonts -->
      <!-- Font Awesome -->
      <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css">
      <!-- Bootstrap core CSS -->
      <link href="{{asset('vendor/mdb/css/bootstrap.min.css')}}" rel="stylesheet">
      <!-- Material Design Bootstrap -->
      <link href="{{asset('vendor/mdb/css/mdb.min.css')}}" rel="stylesheet">
      <link href="{{asset('vendor/bootstrap-tags-input/tagsinput.css')}}" rel="stylesheet">
      {{-- <link href="https://bootstrap-tagsinput.github.io/bootstrap-tagsinput/examples/assets/app.css" rel="stylesheet"> --}}
      
      <!-- Your custom styles (optional) -->
      <link href="{{asset('custom/custom.css')}}" rel="stylesheet">

      <style>
                    /* autocomplete tagsinput*/
            /* .label-info {
            background-color: #5bc0de;
            display: inline-block;
            padding: 0.2em 0.6em 0.3em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25em;
            } */
            .bootstrap-tagsinput{
                padding: .375rem .75rem;
                line-height: 1.5;
            }
            .bootstrap-tagsinput .badge {
                margin: 2px 2px;
                padding: 3px 8px;
            }
      </style>
   </head>
   <body>
       <div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 100vh">
        <div class="container">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{route('search')}}" enctype="multipart/form-data" method="POST" class="py-5 px-2 bg-primary rounded w-100" >
                @csrf
                <div class="row no-gutters">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="">Kata Kunci</label>
                            <div class="d-block">
                                <input type="text" id="input-tags" name="keyword" value="{{old('keyword') ? implode(',',old('keyword')) : ''}}"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="">Kategori</label>
                            <div class="input-default-wrapper">
                                    <input type="text" class="input-default-js">
                                    <input type="file" id="file-with-current" name="dataset" class="input-default-js" accept=".xls,.xlsx"required>
                                    <label class="label-for-default-js rounded mb-0 bg-white" for="file-with-current">
                                       <span class="span-choose-file w-100">Choose
                                       file</span>
                                       {{-- <div class="float-right span-browse"><i class="fas fa-file-excel" aria-hidden="true"></i></div> --}}
                                    </label>
                                 </div>
                        </div>
                        
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="">Kategori</label>
                            <input type="number" value="{{old('kategori')}}" name="kategori" class="form-control category-input">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mt-5 mt-sm-0">
                            <button type="submit" class="btn btn-block btn-md btn-success position-absolute" style="bottom:0">cari</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
           
       </div>
     
      <!-- JQuery -->
      <script type="text/javascript" src="{{asset('vendor/mdb/js/jquery-3.3.1.min.js')}}"></script>
      <!-- Bootstrap tooltips -->
      <script type="text/javascript" src="{{asset('vendor/mdb/js/popper.min.js')}}"></script>
      <!-- Bootstrap core JavaScript -->
      <script type="text/javascript" src="{{asset('vendor/mdb/js/bootstrap.min.js')}}"></script>
      <!-- MDB core JavaScript -->
      <script type="text/javascript" src="{{asset('vendor/mdb/js/mdb.min.js')}}"></script>
      <script type="text/javascript" src="{{asset('vendor/bloodhound/bloodhound.js')}}"></script>
      
      {{-- <script type="text/javascript" src="https://raw.githubusercontent.com/corejavascript/typeahead.js/master/dist/typeahead.bundle.min.js"></script> --}}
      <script type="text/javascript" src="{{asset('vendor/bootstrap-tags-input/tagsinput.js')}}"></script>
      
       <script type="text/javascript" src="{{asset('custom/custom.js')}}"></script>
       <script>
        //    $(document).ready(function(){
                var data = {!!json_encode($categories)!!}
                $('.category-input').customSelect({
                    dataOriginal : data
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
   </body>
</html>