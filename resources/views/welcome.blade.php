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
      <section class="hero">
         <div class="view intro-2">
            <div class="full-bg-img">
               <div class="mask rgba-indigo-slight flex-center">
                  <div class="container">
                     <div class="white-text text-center wow fadeInUp">
                        <form action="{{url('/cari')}}" class="inline-form bg-white rounded py-5" method="POST" enctype="multipart/form-data">
                           @csrf
                           <div class="row justify-content-center">
                              <div class="col-md-4 pr-0" style="margin-right:-1px">
                                 <div class="input-default-wrapper">
                                    <input type="text" name="k" class="form-control rounded-0" style="border-top-left-radius: .25rem!important;
                                       border-bottom-left-radius: .25rem!important;" placeholder="masukan keyword" required>
                                 </div>
                              </div>
                              <div class="col-auto p-0" style="margin-right:-1px">
                                 <div class="input-group">
                                    <select class="form-control rounded-0" id="exampleFormControlSelect1" name="timeseries">
                                       <option value="now 1-d">1 hari terakhir</option>
                                       <option value="now 7-d">7 hari terakhir</option>
                                       <option value="now 1-m">1 bulan terakhir</option>
                                       <option value="today 3-m">3 bulan terakhir</option>
                                       <option value="today 12-m">1 tahun terakhir</option>
                                       <option value="today 5-y">5 tahun terakhir</option>
                                    </select>
                                    <div class="input-group-append">
                                       <span class="input-group-text rounded-0" id="basic-addon2"><i class="fas fa-calendar" aria-hidden="true"></i></span>
                                    </div>
                                 </div>
                              </div>
                              <div class="col-auto pl-0">
                                 <div class="input-default-wrapper">
                                    <input type="text" class="input-default-js">
                                    <input type="file" id="file-with-current" name="dataset" class="input-default-js" required>
                                    <label class="label-for-default-js rounded-right mb-0 bg-white" for="file-with-current">
                                       <span class="span-choose-file">Choose
                                       file</span>
                                       <div class="float-right span-browse"><i class="fas fa-folder" aria-hidden="true"></i></div>
                                    </label>
                                 </div>
                              </div>
                              <div class="col-auto pl-0">
                                 <button type="submit" class="btn btn-md my-0 btn-primary px-5">Cari</button>
                              </div>
                           </div>
                        </form>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      {{-- 
      <div class="container">
         <canvas id="myChart" width="400" height="400" style="width:100%"></canvas>
      </div>
      --}}
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
      <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/locales/bootstrap-datepicker.id.min.js"></script>
      {{-- <script>
         var ctx = document.getElementById('myChart');
         var myChart = new Chart(ctx, {
             type: 'bar',
             data: {
                 labels: {!!json_encode($collections->pluck('geoName')->toArray())!!},
                 datasets: [{
                     data: {!!json_encode($collections->pluck('value')->toArray())!!},
                     backgroundColor: [
                         'rgba(255, 99, 132, 0.2)',
                         'rgba(54, 162, 235, 0.2)',
                         'rgba(255, 206, 86, 0.2)',
                         'rgba(75, 192, 192, 0.2)',
                         'rgba(153, 102, 255, 0.2)',
                         'rgba(255, 159, 64, 0.2)'
                     ],
                     borderColor: [
                         'rgba(255, 99, 132, 1)',
                         'rgba(54, 162, 235, 1)',
                         'rgba(255, 206, 86, 1)',
                         'rgba(75, 192, 192, 1)',
                         'rgba(153, 102, 255, 1)',
                         'rgba(255, 159, 64, 1)'
                     ],
                     borderWidth: 1
                 }]
             },
             options: {
                 scales: {
                     yAxes: [{
                         ticks: {
                             beginAtZero: true
                         }
                     }]
                 }
             }
         });
      </script> --}}
   </body>
</html>