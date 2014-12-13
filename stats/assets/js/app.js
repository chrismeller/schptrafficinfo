var Stats = {
	
	data: null,
	
	init: function () {
		
		jQuery.ajax({
			url: '../stats.json',
			success: function ( data, status, xhr ) {
				Stats.data = data;
				
				Stats.incidents_by_week();
				Stats.incidents_by_day_of_week();
				Stats.incidents_by_type_and_week();
			},
			dataType: 'json'
		});
		
	},
	
	incidents_by_week: function () {

		var seriesData = [];
		var seriesKeys = [];
		jQuery.each( Stats.data.weekly, function ( i, e ) {
			var year = i.substring( 0, 4 );
			var week = i.substring( 4, 6 );
			
			var point = {
				name: year + '-' + week,
				y: parseInt( e, 10 )
			};
			
			seriesKeys.push( parseInt( week, 10 ) );
			seriesData.push( point );
		});

		$('#incidents-by-week').highcharts({
			chart: {
				type: 'spline'
			},
			credits: {
				enabled: false
			},
			legend: {
				enabled: false
			},
			title: {
				text: 'Incidents by Week'
			},
			subtitle: {
				text: 'The number of incidents, by week of the year.'
			},
			xAxis: {
				labels: {
					overflow: 'justify'
				},
				title: {
					text: 'Weeks'
				},
				categories: seriesKeys
			},
			yAxis: {
				title: {
					text: 'Incidents'
				},
				min: 0,
				minorGridLineWidth: 0,
				gridLineWidth: 0,
				alternateGridColor: null,
			},
			tooltip: {
				//valueSuffix: ' m/s'
			},
			plotOptions: {
				spline: {
					lineWidth: 4,
					states: {
						hover: {
							lineWidth: 5
						}
					},
					marker: {
						enabled: false
					},
					//pointInterval: 3600000, // one hour
					//pointStart: Date.UTC(2009, 9, 6, 0, 0, 0)
				}
			},
			series: [
				{
					name: 'Incidents',
					data: seriesData
				}
			],
			navigation: {
				menuItemStyle: {
					fontSize: '10px'
				}
			}
		});
		
	},
	
	incidents_by_day_of_week: function () {
		
		$('#incidents-by-day-of-week').highcharts({
			chart: {
				type: 'column'
			},
			credits: {
				enabled: false
			},
			title: {
				text: 'Incidents by Day of Week'
			},
			subtitle: {
				text: 'The number of incidents, by day of the week.'
			},
			xAxis: {
				type: 'category',
				labels: {
					rotation: -45
				},
				title: {
					text: 'Days'
				},
				categories: [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ]
			},
			yAxis: {
				min: 0,
				title: {
					text: 'Incidents'
				}
			},
			legend: {
				enabled: false
			},
			series: [{
				name: 'Incidents',
				data: Stats.data.dayofweek
			}]
		});
		
	},
	
	incidents_by_type_and_week: function () {

		var serieses = [];
		var seriesKeys = [];
		jQuery.each( Stats.data.typebyweek, function ( series, weeks ) {
			
			var seriesData = [];
			
			jQuery.each( weeks, function ( i, incidents ) {
				var year = i.substring( 0, 4 );
				var week = i.substring( 4, 6 );
			
				var point = {
					name: year + '-' + week,
					y: parseInt( incidents, 10 )
				};
				
				seriesKeys.push( parseInt( week, 10 ) );
				seriesData.push( point );
			});
			
			serieses.push( { name: series, data: seriesData } );
			
		});

		$('#incidents-by-type-and-week').highcharts({
			chart: {
				type: 'spline'
			},
			credits: {
				enabled: false
			},
			legend: {
				enabled: false
			},
			title: {
				text: 'Incidents by Type and Week'
			},
			subtitle: {
				text: 'The number of incidents, by type and week of the year.'
			},
			xAxis: {
				labels: {
					overflow: 'justify'
				},
				title: {
					text: 'Weeks'
				},
				categories: seriesKeys
			},
			yAxis: {
				title: {
					text: 'Incidents'
				},
				min: 0,
				minorGridLineWidth: 0,
				gridLineWidth: 0,
				alternateGridColor: null,
			},
			tooltip: {
				shared: true
			},
			plotOptions: {
				spline: {
					lineWidth: 4,
					states: {
						hover: {
							lineWidth: 5
						}
					},
					marker: {
						enabled: false
					},
					//pointInterval: 3600000, // one hour
					//pointStart: Date.UTC(2009, 9, 6, 0, 0, 0)
				}
			},
			series: serieses,
			navigation: {
				menuItemStyle: {
					fontSize: '10px'
				}
			}
		});
		
	}
};

Stats.init();