function GoogleDraw(onLoad) {
  		return;
    google.charts.load('current', {'packages':['geochart']});
    google.charts.setOnLoadCallback(onLoad);
}
GoogleDraw.prototype = {
    container: null,
    geomap: null,
    init: function(container, imageLinkElement){
  		return;
    	this.container = container;
    	this.geomap = new google.visualization.GeoChart($(container)[0]);
    	var slef = this;
		google.visualization.events.addListener(this.geomap, 'ready', function () {
			$(imageLinkElement).prop("href", slef.geomap.getImageURI());
	    });
    },
  	drawMap: function(regionData, regionColors, displayMode) {
  		return;
  		regionData = google.visualization.arrayToDataTable(regionData);

        var options = {
        	
        };
        options['width'] = window.screen.width/1.2;
        options['height'] = window.screen.height/1.2;

        options['region'] = "US";
    	options['legend'] = {textStyle: {color: 'black', fontSize: 14}, numberFormat: '##'};
    	options['resolution'] = 'provinces';//'metros';
        options['displayMode'] = displayMode;
    	options['colorAxis'] = regionColors;
    	this.geomap.draw(regionData, options);
  	}
}