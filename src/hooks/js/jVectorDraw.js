function jVectorDraw() {
}
jVectorDraw.prototype = {
    container: null,
    geomap: null,
    regionData: null,
    isCounty: null,
    init: function(container, imageLinkElement, isCounty){
    	this.container = container;
        this.isCounty = isCounty;
    },
  	drawMap: function(regionData, regionColors, legentTitle, legend_func, maxVal, markers) {
        this.regionData = regionData;
        self = this;
        //this.regionColors = regionColors;
        //alert(JSON.stringify(regionData.data));
        var mpFile = this.isCounty == true ? 'map_map' : 'us_aea_en';
        if (!markers){
            markers = [{}];
        }

        this.geomap = $(this.container).vectorMap({
            //map: 'map_map',
            map: mpFile,
            backgroundColor:  "#FFFFFF",
            regionStyle: {
                initial: {
                    fill: '#f5f5f5',
                    'stroke-width': 0.1,
                    stroke: '#999999',
                    opacity: 1
                },
                hover: {
                    //fill: '#D5D5D5',
                    opacity: 0.7
                }
            },
            markerStyle: {
                initial: {
                    fill: '#F8E23B',
                    //fill: 'blue',
                    stroke: '#383f47',
                    //r: 15
                }
            },
            markers: markers,
            //[{
                //latLng: [42.349768,-71.104888], name: 'Vatican City'
            //}],
            series: {
                regions: [{
                    scale: regionColors.colors,
                    attribute: 'fill',
                    values: regionData.data,
                    legend: {
                        vertical: false,
                        title: legentTitle,
                        labelRender: legend_func
                    },
                //min: 0,
                max: maxVal
                }],
            },
            onMarkerTipShow: function(event, label, index){
                label.html(
                    //'<b>'+index+'</b><br/>'
                  '<b>'+markers[index].Name+'</b><br/>' +
                  '<b>Respones: '+markers[index].count+'</b><br/>'
                );
            },
            onRegionTipShow: function(event, label, code){
                label.html(
                  '<b>'+label.html()+'</b></br>'+self.regionData.info[code]
                );
            }      
        }).vectorMap('get', 'mapObject');
        this.resize();
  	},
    resize: function(){
        $(this.container).width($( document ).width()/1.2);
        $(this.container).height($( document ).height()/1.2);
        this.geomap.updateSize();
    }
}