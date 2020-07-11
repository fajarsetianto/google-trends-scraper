@extends('pages.layout')

@section('css')
    <link href="{{asset('vendor/bootstrap-tags-input/tagsinput.css')}}" rel="stylesheet">
    <link href="{{asset('custom/custom.css')}}" rel="stylesheet">
@endsection

@section('js')
    <script src="{{asset('vendor/limitless/global_assets/js/plugins/visualization/echarts/echarts.min.js')}}"></script>
    <script src="{{asset('vendor/limitless/global_assets/js/plugins/forms/styling/uniform.min.js')}}"></script>
    <script src="https://momentjs.com/downloads/moment-with-locales.min.js"></script>
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
                $('.form-check-input-styled').uniform();
            // })
    </script>
    <script>
        
        moment.locale('id');
        var data = {!!json_encode($queue->data)!!}
        var keywords = {!!json_encode($queue->keywords)!!}
        var legends = {!!json_encode($queue->keywords)!!};
        legends.unshift('dataset');
        var labels = data.map(function(data){
            if(data.start_date == data.end_date){
               data = moment(data.start_date).format('D MMM YYYY');
            }else{
               data = moment(data.start_date).format('D MMM YYYY')+ ' - '+ moment(data.end_date).format('D MMM YYYY');
            }
            return data;
        })
        var series = [];
        series.push({
            name: 'dataset',
            type: 'line',
            smooth: true,
            symbolSize: 6,
            itemStyle: {
                normal: {
                    borderWidth: 1
                }
            },
            lineStyle: {
                width: 3
            },
            data : data.map(function(val, index){
                return val['value'];
            })
        })

        keywords.forEach(function(keyword){
            var maped = data.map(function(val, index){
               return val['trends'][keyword];
            })
            series.push({
                name: keyword,
                type: 'line',
                smooth: true,
                symbolSize: 4,
                itemStyle: {
                    normal: {
                        borderWidth: 1
                    }
                },
                lineStyle: {
                    width: 2
                },
                data : maped
            })
         });
         colors = [];
         h = Math.round(360/legends.length);
         legends.forEach(function(){
            colors.push(get_random_color(h))
            h += h
         })
        
        var line_basic_element = document.getElementById('line_basic');
        var line_basic = echarts.init(line_basic_element);
        line_basic.setOption({
            
            // Define colors
            color: colors,

            // Global text styles
            textStyle: {
                fontFamily: 'Roboto, Arial, Verdana, sans-serif',
                fontSize: 13
            },

            // Chart animation duration
            animationDuration: 750,

            // Setup grid
            grid: {
                left: 0,
                right: 40,
                top: 35,
                bottom: 60,
                containLabel: true
            },

            // Add legend
            legend: {
                data: legends,
                itemHeight: 8,
                itemGap: 20
            },

            // Add tooltip
            tooltip: {
                trigger: 'axis',
                backgroundColor: 'rgba(0,0,0,0.75)',
                padding: [10, 15],
                textStyle: {
                    fontSize: 13,
                    fontFamily: 'Roboto, sans-serif'
                }
            },

            // Horizontal axis
            xAxis: [{
                type: 'category',
                boundaryGap: false,
                axisLabel: {
                    color: '#333'
                },
                axisLine: {
                    lineStyle: {
                        color: '#999'
                    }
                },
                data: labels
            }],

            // Vertical axis
            yAxis: [{
                type: 'value',
                axisLabel: {
                    formatter: '{value} ',
                    color: '#333'
                },
                axisLine: {
                    lineStyle: {
                        color: '#999'
                    }
                },
                splitLine: {
                    lineStyle: {
                        color: ['#eee']
                    }
                },
                splitArea: {
                    show: true,
                    areaStyle: {
                        color: ['rgba(250,250,250,0.1)', 'rgba(0,0,0,0.01)']
                    }
                }
            }],

            // Zoom control
            dataZoom: [
                {
                    type: 'inside',
                    start: 0,
                    end: 100,
                    minValueSpan: 1,
                },
                {
                    show: true,
                    type: 'slider',
                    start: 0,
                    end: 100,
                    height: 40,
                    bottom: 0,
                    borderColor: '#ccc',
                    fillerColor: 'rgba(0,0,0,0.05)',
                    handleStyle: {
                        color: '#585f63'
                    }
                }
                ,
            ],

            // Add series
            series: series,
            });
            var triggerChartResize = function() {
                line_basic_element && line_basic.resize();
            };

            // On sidebar width change
            var sidebarToggle = document.querySelector('.sidebar-control');
            sidebarToggle && sidebarToggle.addEventListener('click', triggerChartResize);

            // On window resize
            var resizeCharts;
            window.addEventListener('resize', function() {
                clearTimeout(resizeCharts);
                resizeCharts = setTimeout(function () {
                    triggerChartResize();
                }, 200);
            });

            line_basic.on('datazoom',function(evt){
                var axis = line_basic.getModel().option.xAxis[0];
                calculateCorellation(axis.rangeStart,axis.rangeEnd)
            });

            

            function calculateCorellation(start, end){
                if(start != null || end != null){
                    currentData = data.slice(start,end+1);
                }else{
                    currentData = data;
                }
                n = currentData.length;
                sigmaX = currentData.reduce(function(a,b){
                    return { value:a.value + b.value}
                }).value;
                sigmaX2 = currentData.map(function(data){
                    return Math.pow(data.value,2);
                }).reduce(function(a,b){
                    return a + b;
                })

                corellation = [];
                keywords.forEach(function(keyword){
                    keywordData = currentData.map(function(data){
                        return data.trends[keyword];
                    })
                    sigmaY = keywordData.reduce(function(a,b){
                        return a + b;
                    })
                    sigmaY2 = keywordData.map(function(data){
                        return Math.pow(data,2);
                    }).reduce(function(a,b){
                        return a+b;
                    })
                    sigmaXY = 0;
                    for (let index = 0; index < currentData.length; index++) {
                        sigmaXY += currentData[index].value * currentData[index].trends[keyword];
                    }

                    ul = (sigmaXY - ((sigmaX * sigmaY) / n));
                    lo = Math.sqrt((sigmaX2 - (Math.pow(sigmaX,2) / n)) * (sigmaY2 - (Math.pow(sigmaY,2) / n)));
                    // console.log(ul);
                    
                    corellation.push({
                        key: keyword,
                        value : ul/lo
                    })
                    // corellation.push([keyword] : ul/lo);
                });

                $('#corellation-list').html('');
                // console.log(corellation);
                corellation.forEach(function(value, index){
                    // console.log('loop')
                    $('#corellation-list').append(
                        $('<li>').addClass('list-group-item').append([
                            $('<span>').addClass('text-uppercase').html((index+1)+'. '+value.key),
                            $('<span>').addClass('ml-auto').html(value.value),
                        ])
                    )
                });
                corellationPeriod = moment(currentData[0].start_date).format('D MMM YYYY')+' - '+moment(currentData[currentData.length-1].end_date).format('D MMM YYYY');
                $('#corellation-period').html(
                    'period : '+corellationPeriod
                )

            }

            calculateCorellation(null,null);
            function rand(min, max) {
                return parseInt(Math.random() * (max-min+1), 10) + min;
            }

            function get_random_color(h) {
                var h = h; // color hue between 1 and 360
                var s = rand(30, 100); // saturation 30-100%
                var l = rand(30, 70); // lightness 30-70%
                return 'hsl(' + h + ',' + s + '%,' + l + '%)';
            }
    </script>
    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                // var reader = new FileReader();
                // reader.onload = function(e) {
                //     $('#blah').attr('src', e.target.result);
                // }
                // reader.readAsDataURL(input.files[0]); // convert to base64 string
                $(".form-check-input-styled").prop('checked',false);
            }else{
                $(".form-check-input-styled").prop('checked',true);
            }
            $.uniform.update();
        }

        $("body").on('change','input[name="dataset"]',function() {
            readURL(this);
        });

        // $(".form-check-input-styled").change(function(){
        //     if(this.checked){
        //         $('body .uniform-uploader').replaceWith(
        //             $('<input>').attr({
        //                 'type' : 'file',
        //                 'accept' : '.xls,.xlsx',
        //                 'name' : 'dataset',
        //                 'class' : 'form-input-styled'
        //             })
        //             .prop('data-fouc',true)
        //         )
        //         $('body .form-input-styled').uniform({
        //             fileButtonClass: 'action btn bg-info-400'
        //         })
        //     }
        // })
    </script>
@endsection

@section('content')
<div class="card container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="p-3">
                <form action="{{route('search',[$queue->id])}}" enctype="multipart/form-data" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Keywords</label>
                        <div class="d-block">
                            <input type="text" placeholder="Type Keywords" id="input-tags" name="keyword" value="{{old('keyword') ? implode(',',old('keyword')) : implode(',',$queue->keywords)}}" required/>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <input type="number" value="{{old('kategori') ? old('kategori') : $queue->category}}" name="kategori" class="form-control category-input" required>
                    </div>
                    <div class="form-group">
                        <label>Dataset</label>
                        <input type="file" name="dataset" class="form-input-styled" data-fouc accept=".xls,.xlsx">
                        <span class="form-text text-muted">Accepted formats: xls, xlsx, csv. Max file size 2Mb or <a href="">Download the example dataset</a></span>
                        <div class="form-check">
                            <label class="form-check-label">
                                <input type="checkbox" name="use_old" class="form-check-input-styled" checked data-fouc>
                                Or use latest dataset
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-info">Submit</button>
                </form>
            </div>
        </div>
        <div class="col-md-6"></div>
    </div>
</div>
<div class="container">
    <div class="card">
        <div class="card-body">
            <div class="chart-container">
                <div class="chart has-fixed-height" id="line_basic"></div>
            </div>
            {{-- <div class="card">
                <div class="card-header header-elements-inline">
                    <h4 class="mb-0">Corellation Results</h4>
                    <div class="header-elements">
                        <span id="corellation-period"></span>
                    </div>
                </div>
                
                <ul id="corellation-list" class="list-group list-group-flush border-top"></ul>
            </div> --}}
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h4 class="mb-0">Corellation Results</h4>
                    <div class="header-elements">
                        <span id="corellation-period"></span>
                    </div>
                </div>
                
                <ul id="corellation-list" class="list-group list-group-flush border-top"></ul>
                
            </div>
        </div>
        {{-- <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <ul class="list-group">
                        @foreach($queue->keywords as $keyword)
                            <li class="list-group-item">{{$keyword}} {{$queue->corelation[$keyword]}}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            
        </div> --}}
    </div>
</div>
    
    
@endsection

