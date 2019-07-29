<template>
    <div>
    </div>
</template>
<script>
    const googleTrends = require('google-trends-api');
    export default{
        name: 'main-app',
        mounted() {
            googleTrends.interestByRegion({keyword: 'Donald Trump', startTime: new Date('2017-02-01'), endTime: new Date('2017-02-06'), resolution: 'CITY'})
            .then((res) => {
                console.log(res);
            })
            .catch((err) => {
                console.log(err);
            })
        }
    }
</script>