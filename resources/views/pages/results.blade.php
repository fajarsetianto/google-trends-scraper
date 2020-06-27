@extends('pages.layout')

@section('css')

@endsection

@section('js')
    <script src="{{asset('vendor/limitless/global_assets/js/plugins/visualization/echarts/echarts.min.js')}}"></script>
    <script src="https://momentjs.com/downloads/moment-with-locales.min.js"></script>
    <script>
        var dataset = {!!json_encode($queue->dataset)!!}
        var keywords = {!!json_encode($queue->keywords)!!}
        var legends = {!!json_encode($queue->keywords)!!};
        legends.unshift('dataset');
        var labels = dataset.map(function(data){
            if(data.start_date == data.end_date){
               data = moment(data.start_date).format('D/M/YY');
            }else{
               data = moment(data.start_date).format('D/M/YY')+ ' s.d '+ moment(data.end_date).format('D/M/YY');
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
            data : dataset.map(function(val, index){
                return val['value'];
            })
        })

        keywords.forEach(function(keyword){
            var maped = dataset.map(function(val, index){
               return val['keywords'][keyword];
            })
            series.push({
                name: keyword,
                type: 'line',
                smooth: true,
                symbolSize: 6,
                itemStyle: {
                    normal: {
                        borderWidth: 1
                    }
                },
                data : maped
            })
         });



        var line_basic_element = document.getElementById('line_basic');
        var line_basic = echarts.init(line_basic_element);
        line_basic.setOption({
            title: {
                text: 'ECharts entry example'
            },
            // Define colors
            color: ["#424956", "#d74e67", '#0092ff'],

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
                    end: 100
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
                    currentDataset = dataset.slice(start,end+1);
                }else{
                    currentDataset = dataset;
                }
                n = currentDataset.length;
                sigmaX = currentDataset.reduce(function(a,b){
                    return { value:a.value + b.value}
                }).value;
                sigmaX2 = currentDataset.map(function(data){
                    return Math.pow(data.value,2);
                }).reduce(function(a,b){
                    return a + b;
                })

                corellation = [];
                keywords.forEach(function(keyword){
                    keywordDataset = currentDataset.map(function(data){
                        return data.keywords[keyword];
                    })
                    sigmaY = keywordDataset.reduce(function(a,b){
                        return a + b;
                    })
                    sigmaY2 = keywordDataset.map(function(data){
                        return Math.pow(data,2);
                    }).reduce(function(a,b){
                        return a+b;
                    })
                    sigmaXY = 0;
                    for (let index = 0; index < currentDataset.length; index++) {
                        sigmaXY += currentDataset[index].value * currentDataset[index].keywords[keyword];
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
                corellation.forEach(function(value){
                    // console.log('loop')
                    $('#corellation-list').append(
                        $('<li>').addClass('list-group-item').html(
                            value.key +' '+ value.value
                        )
                    )
                });

            }

            calculateCorellation(null,null);
    </script>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="chart-container">
                <div class="chart has-fixed-height" id="line_basic"></div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Corellation Results</h4>
                </div>
                <div class="card-body">
                    <ul id="corellation-list" class="list-group"></ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <ul class="list-group">
                        @foreach($queue->keywords as $keyword)
                            <li class="list-group-item">{{$keyword}} {{$queue->corelation[$keyword]}}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            
        </div>
    </div>
@endsection

