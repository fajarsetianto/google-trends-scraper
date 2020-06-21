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
            <h4 id="detail-progress" class="text-center" data-status="">Adding Job to Queue</h4>
            <div class="d-flex" style="justify-content: space-around">
                <div style="flex-basis: 24%">
                    <div class="progress">
                        <div id="progress-1" class="progress-bar " role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div style="flex-basis: 24%">
                    <div class="progress">
                        <div id="progress-2" class="progress-bar " role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div style="flex-basis: 24%">
                    <div class="progress">
                        <div id="progress-3" class="progress-bar " role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div style="flex-basis: 24%">
                    <div class="progress">
                        <div id="progress-4" class="progress-bar " role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
            <div id="note" class="alert alert-warning d-none m-3 text-small" role="alert"></div>
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

      <script src="https://momentjs.com/downloads/moment-with-locales.min.js"></script>
      <script type="text/javascript" src="{{asset('vendor/bloodhound/bloodhound.js')}}"></script>
      
      
      {{-- <script type="text/javascript" src="https://raw.githubusercontent.com/corejavascript/typeahead.js/master/dist/typeahead.bundle.min.js"></script> --}}
      <script type="text/javascript" src="{{asset('vendor/bootstrap-tags-input/tagsinput.js')}}"></script>
      
       <script type="text/javascript" src="{{asset('custom/custom.js')}}"></script>
       {{-- <script>
            const detailProgress = $('#detail-progress');
            var periods = {!!json_encode($formatedPeriods)!!}
            var keywords = {!!json_encode($input['keyword'])!!}
            var dataset = {!!json_encode($dataSet)!!}
            var category = {!!json_encode($input['kategori'])!!}
            var url = "{!!route("fetch")!!}";
            var step = 100 / (periods.length);
            var i = 0;
            fetching();


            async function fetching(){
                try{
                    detailProgress.html('Mengambil data dari Google Trends')
                    for (let index = 0; index < periods.length; index++) {
                        var currentStartDate =  moment(periods[index].start_date);
                        var currentEndDate =  moment(periods[index].end_date);
                        var currentDataSet = await $.post(
                            url,
                            {
                                _token : '{{csrf_token()}}',
                                start_date : currentStartDate.format('YYYY-MM-DD'),
                                end_date : currentEndDate.format('YYYY-MM-DD'),
                                keywords : keywords,
                                category : category
                            })
                        
                        if(currentDataSet['TIMESERIES'].length == 0){
                            dataset.map(function(data){
                                if(moment(data['start_date']).isBetween(currentStartDate, currentEndDate) || moment(data['end_date']).isBetween(currentStartDate, currentEndDate)){
                                    data['keywords'] = {};
                                    keywords.forEach(function(keyword){
                                        data['keywords'][keyword] = 0
                                    })
                                }
                                return data;
                            })
                        }else{
                            dataset.filter(function(datafilter){
                                return datafilter['keywords'] === undefined
                            }).forEach(function(data){
                                var dataStartDate = moment(data['start_date']);
                                var dataEndDate = moment(data['end_date']);

                                filteredTimeseries = currentDataSet['TIMESERIES'].filter(function(timeseries){
                                    return moment.unix(timeseries.time).isBetween(dataStartDate, dataEndDate)
                                })

                                if(filteredTimeseries.length != 0){
                                    data['keywords'] = {};
                                    keywords.forEach(function(keyword, index){
                                        value = filteredTimeseries.reduce(function(total, element){
                                            return total + element.value[index];
                                        },0)
                                        data['keywords'][keyword] = value;
                                    });
                                }
                            })
                        }
                        i += step;
                        $('#fetch-progress').width((i) +'%').html((Math.ceil(i)) +'%')
                    }
                }catch(err){
                    console.log(err)
                }
            }

            // function fetch(){
            //     detailProgress.html('Mengambil data dari Google Trends')

            //     var requests = [];
            //     var step = 100 / (periods.length);
            //     var i = 0;
            //     periods.forEach(function(element, index){
            //         var start_date = moment(element.start_date);
            //         var end_date = moment(element.end_date);

            //         $.ajax({
            //             url: '{{route("fetch")}}',
            //             method: 'POST',
            //             data: {
            //                 _token : '{{csrf_token()}}',
            //                 start_date : start_date.format('YYYY-MM-DD'),
            //                 end_date : end_date.format('YYYY-MM-DD'),
            //                 keywords : keywords,
            //                 category : category
            //             },
            //             success: function(response){
            //                 periods[index].timeseries = response['TIMESERIES'];
            //                 i += step;
            //                 $('#fetch-progress').width((i) +'%').html((Math.ceil(i)) +'%')
            //             }
            //         })
            //     });
            // }
            
            // $.when.apply(null,requests).then(function(){
            //     $.each(arguments, function(i,row){
            //         console.log(row)
            //     })
            // });
                
            
       </script> --}}
       <script>
           (function(){
            const detailProgress = $('#detail-progress');
            const note = $('#note');
            check();
            async function check(){
                try{
                    let response = await $.get('{{route("queue",[$queue->id])}}')
                    if(response.status != detailProgress.data('status') || response.status == 1){
                        
                        switch(response.status){
                            case 0:
                                detailProgress.html('Error');
                                break;
                            case 1:
                                detailProgress.html('1. Adding job to queue');
                                $('#progress-1').each(function(){
                                    for (let index = 1; index <= 100; index++) {
                                        $(this).css('width',index+'%')
                                    }
                                })
                                note.html('Sorry we are a little busy at the moment. We will process your request after '+response.jobs_a_head+' other requests').removeClass('d-none')
                                break;
                            case 2:
                                note.addClass('d-none')
                                detailProgress.html('2. Get data from Google Trend');
                                $('#progress-1,#progress-2').each(function(){
                                    for (let index = 1; index <= 100; index++) {
                                        $(this).css('width',index+'%')
                                    }
                                })
                                break;
                            case 3:
                                note.addClass('d-none')
                                detailProgress.html('3. Normalizing Data');
                                $('#progress-1,#progress-2,#progress-3').each(function(){
                                    for (let index = 1; index <= 100; index++) {
                                        $(this).css('width',index+'%')
                                    }
                                })
                                break;
                            case 4:
                                note.addClass('d-none')
                                $('#progress-1,#progress-2,#progress-3,#progress-4').each(function(){
                                    for (let index = 1; index <= 100; index++) {
                                        $(this).css('width',index+'%')
                                    }
                                })
                                detailProgress.html('4. Preparing Result');
                                break
                        }
                        detailProgress.data('status', response.status)
                    }
                    
                    if(response.status != 4 && response.status != 0 ){
                        check()
                    }
                }catch(error){
                    console.log(error);
                }
            }
           }())
           
       </script>
    </body>
</html>