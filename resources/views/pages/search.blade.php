<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="full-height">
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
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/css/bootstrap-datepicker.min.css">
      <!-- Your custom styles (optional) -->
      <link href="{{asset('vendor/mdb/css/style.css')}}" rel="stylesheet">

   </head>
   <body>
       <header>
            <nav class="navbar fixed-top navbar-expand-lg navbar-dark bg-primary scrolling-navbar">
            <a class="navbar-brand" href="{{url('/')}}"><strong>GTrend</strong></a>
                <form action="{{route('search',[$queue->id])}}" class="inline-form rounded py-1 w-100" method="POST" enctype="multipart/form-data">
                  @csrf  
                  <div class="row justify-content-center">
                       <div class="col-md-6 pr-0" style="margin-right:-1px">
                          <div class="input-default-wrapper">
                             <input type="text" name="k" class="form-control rounded-0" style="border-top-left-radius: .25rem!important;
                                border-bottom-left-radius: .25rem!important;" placeholder="masukan keyword" value="{{implode(',',$results->corelations->pluck('keyword')->toArray())}}" required>
                          </div>
                       </div>
                       <div class="col-md-4 pl-0">
                          <div class="input-default-wrapper">
                             <input type="text" class="input-default-js">
                             <input type="file" id="file-with-current" name="dataset" class="input-default-js" required>
                             <label class="label-for-default-js rounded-right mb-0 bg-white" for="file-with-current">
                                <span class="span-choose-file">Choose
                                file</span>
                                <div class="float-right span-browse"><i class="fas fa-file-excel" aria-hidden="true"></i></div>
                             </label>
                          </div>
                       </div>
                       <div class="col-auto pl-0">
                          <button type="submit" class="btn btn-md btn-white my-0 btn-primary px-5">Cari</button>
                       </div>
                    </div>
                 </form>
            </nav>
                
       </header>
      <div class="container" style="margin-top:100px">
          <div class="row">
              <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                           Korelasi Google trend "<i>{{$results->corelations->pluck('keyword')->join(', ',' dan')}}</i>"
                        </div>
                        <div class="card-body">
                                <canvas id="myChart"></canvas>
                                <div class="row">
                                   <div class="col-md-6">
                                    <div class="card">
                                       <div class="card-header">
                                          Hasil Korelasi Pearson
                                       </div>
                                       <div class="card-body">
                                           <table class="table">
                                                 <thead>
                                                    <tr>
                                                       @foreach($results->corelations as $corelation)
                                                          <th>{{$corelation['keyword']}}</th>
                                                        @endforeach
                                                    </tr>
                                                 </thead>
                                                 <tbody>
                                                    <tr>
                                                       @foreach($results->corelations as $corelation)
                                                          <td>{{$corelation['value']}}</td>
                                                       @endforeach
                                                    </tr>
                                                 </tbody>
                                              </table>
                                        </div>
                                    </div>
                                   </div>
                                   <div class="col-md-6">
                                    <div class="card">
                                       <div class="card-header">
                                          Kueri Terkait
                                       </div>
                                       <div class="card-body">
                                           <table class="table">
                                                 <tbody>
                                                    <tr>
                                                       
                                                    </tr>
                                                 </tbody>
                                              </table>
                                        </div>
                                    </div>
                                   </div>
                                </div>
                                
                                
                        </div>
                        
                    </div>
                    
              </div>
          </div>
          <div class="table-responsive">
             
          </div>
      </div>
      
     
      {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.bundle.js"></script> --}}
      <!-- SCRIPTS -->
      <!-- JQuery -->
      <script type="text/javascript" src="{{asset('vendor/mdb/js/jquery-3.3.1.min.js')}}"></script>
      <!-- Bootstrap tooltips -->
      <script type="text/javascript" src="{{asset('vendor/mdb/js/popper.min.js')}}"></script>
      <!-- Bootstrap core JavaScript -->
      <script type="text/javascript" src="{{asset('vendor/mdb/js/bootstrap.min.js')}}"></script>
      <!-- MDB core JavaScript -->
      <script type="text/javascript" src="{{asset('vendor/mdb/js/mdb.min.js')}}"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/locales/bootstrap-datepicker.id.min.js"></script>
      
      <script>

         var keywords = {!!json_encode($results->corelations->pluck('keyword'))!!};
         var dataSets = {!!json_encode($results->dataSet)!!};
         var data = [];

         data.push({
               label : 'kasus',
               fill: false,
               backgroundColor : 'red',
               borderColor: 'red',
               data : dataSets.map(function(val, index){
                     return val['cases'];
                  })
            })
         var dynamicColors = function() {
            var r = Math.floor(Math.random() * 255);
            var g = Math.floor(Math.random() * 255);
            var b = Math.floor(Math.random() * 255);
            return "rgb(" + r + "," + g + "," + b + ")";
         };
         keywords.forEach(function(keyword){
            var filtered = dataSets.map(function(val, index){
               return val[keyword.replace(/ /g,'')];
            })
            var color = dynamicColors();
            data.push({
               label : keyword,
               fill: false,
               backgroundColor : color,
               borderColor: color,
               borderWidth: 1,
               data : filtered
            })
         });
         var config = {
			type: 'line',
			data: {
				labels: {!!json_encode($results->dataSet->pluck('formatedDate')->toArray())!!},
				datasets: data
			},
			options: {
				responsive: true,
				title: {
					display: true,
					text: "Grafik Korelasi {!!$results->corelations->pluck('keyword')->join(', ',' dan')!!}"
				},
				scales: {
					xAxes: [{
						display: true,
                  beginAtZero: true,
						ticks: {
							callback: function(dataLabel, index) {
								// Hide the label of every 2nd dataset. return null to hide the grid line too
								return dataLabel;
							}
						}
					}],
					yAxes: [{
						display: true,
						beginAtZero: true
					}]
				}
			}
		};
         var ctx = document.getElementById('myChart');
         
         var myChart = new Chart(ctx, config);
             
         $('#triggerCustomDates').click(function(){
             $('#customDatesModal').modal('show')
         })

         $('#pickadate').datepicker({
            format: "dd-M-yyyy",
            autoclose: true,
            language: "id"
         });
      </script>
      
      
   </body>
</html>