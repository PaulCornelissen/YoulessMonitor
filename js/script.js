/*
This file is part of Youless Monitor.

Youless Monitor is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Youless Monitor is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Youless Monitor.  If not, see <http://www.gnu.org/licenses/>.
*/

var loadingEnabled = false;

// Live chart function
var tmpWatt = 0;

function requestLiveData() {
    $.ajax({
        url: 'ajax.php?a=live',
        dataType: 'json',
        success: function(json) {
			var interval = $('#settingsOverlay').data('liveinterval');
			var shiftMax = $('#settingsOverlay').data('livelengte') / interval;

        	if(json === null) {
        		// apparently we got a bad response, let's try again in a bit
        		// console.log("Bad request caught!");
        		setTimeout(requestLiveData, interval); 
        		return;
        	}
        	
        	// check to see if the response was positive
        	if(json["ok"] == 0) {
	        	// json error
				$('#message').text(json["msg"]);
				$('#overlaySucces').fadeIn();
	        }
	        else {

	        	// add the point
	            var x = (new Date()).getTime();
	            var y = json["pwr"];

	            var series = chart.series[0],
	                shift = series.data.length > shiftMax; // shift if the series is longer than shiftMax

	            //    console.log("Interval: " + interval + " livelengte: " + $('#settingsOverlay').data('livelengte') + " shiftmax: " + shiftMax + " shift: " + shift);


	            chart.series[0].addPoint([x, y], true, shift);

	            // up/down indicator
	            if(tmpWatt < parseInt(json["pwr"])){
	            	updown = "countUp";
	            }
	            else if(tmpWatt == parseInt(json["pwr"])){
	            	updown = "";
	            }
	            else
	            {
	            	updown = "countDown";
	            }
	            tmpWatt = parseInt(json["pwr"]);
	            
	            // update counter
	            $('#wattCounter').html("<span class='"+updown+"'>"+json["pwr"]+" Watt</span>");            

				// getMeter();
	            
	            // call it again after set interval
	            setTimeout(requestLiveData, interval);

	        }
        },
	    error: function (xhr, ajaxOptions, thrownError) {
	        // an error occured, let's try again in a bit
            // call it again after set interval
			var interval = $('#settingsOverlay').data('liveinterval');
            setTimeout(requestLiveData, interval);   
	    },
        cache: false
    });
}
// Calculate costs/kwh function
function calculate(target, date){
	$('#kwhCounter').html("<span style='line-height:30px;font-style:italic;'>Loading…</span>");
	$('#cpkwhCounter').html("<span style='line-height:30px;font-style:italic;'>Loading…</span>");	
	return;
		
	$.ajax({
		url: 'ajax.php?a=calculate_'+target+'&date='+date,
		dataType: 'json',
		success: function( jsonData ) {
		
			// KWH and costs counter
				if($('input[name=dualcount]:checked').val() == 1)
				{
					$('#kwhCounter').html("<span>H: "+jsonData["kwh"]+" kWh<br>L: "+jsonData["kwhLow"]+" kWh<br>T: "+jsonData["kwhTotal"]+" kWh</span>");
					$('#cpkwhCounter').html("<span>H: € "+jsonData["price"]+" <br>L: € "+jsonData["priceLow"]+" <br>T: € "+jsonData["priceTotal"]+"</span>");
				}
				else
				{
					$('#kwhCounter').html("<span style='line-height:30px;'>"+jsonData["kwh"]+" kWh</span>");
					$('#cpkwhCounter').html("<span style='line-height:30px;'>€ "+jsonData["price"]+"</span>");
				}				
		},
		cache: false
	});	
}				

function calculateRange(min,max){

	$('#range').html("<span>S: " + Highcharts.dateFormat('%d-%m-%Y %H:%M:%S', min) +"<br>E: " + Highcharts.dateFormat('%d-%m-%Y %H:%M:%S', max) + "</span>");	
							
	$.ajax({
		url: 'ajax.php?a=calculate_range&stime='+Math.floor(min/1000)+'&etime='+Math.floor(max/1000),
		dataType: 'json',
		success: function( jsonData ) {
			if($('input[name=dualcount]:checked').val() == 1)
			{
				$('#kwhCounter').html("<span>H: "+jsonData["kwh"]+" kWh<br>L: "+jsonData["kwhLow"]+" kWh<br>T: "+jsonData["kwhTotal"]+" kWh</span>");
				$('#cpkwhCounter').html("<span>H: € "+jsonData["price"]+" <br>L: € "+jsonData["priceLow"]+" <br>T: € "+jsonData["priceTotal"]+"</span>");
			}
			else
			{
				$('#kwhCounter').html("<span style='line-height:30px;'>"+jsonData["kwh"]+" kWh</span>");
				$('#cpkwhCounter').html("<span style='line-height:30px;'>€ "+jsonData["price"]+"</span>");
			}				
								
		}
								
	});
}

function refreshData(target, date){
	$.ajax({
		url: 'cronjob.php',
		success: function(result) { 
			createChart(target, date);
		}
	});
}

function getMeter() {
	$.ajax({
		url: 'ajax.php?a=get_meter',
		dataType: 'json',
		success: function( jsonData ) {
			if($('input[name=dualcount]:checked').val() == 1)
			{
				if ( jsonData["islow"]  == '0' ) { 
					$('#meter').html("<span class='isLow'>H: "+jsonData["meter"]+" kWh<br></span><span>L: "+jsonData["meterl"]+" kWh</span>");
				} else {
					$('#meter').html("<span>H: "+jsonData["meter"]+" kWh<br></span><span class='isLow'>L: "+jsonData["meterl"]+" kWh</span>");
				}
			}
			else
			{
				$('#meter').html("<span style='line-height:30px;'>"+jsonData["meter"]+" kWh</span>");
			}				
								
			setTimeout(getMeter, 60 * 1000);    
		}
	});
} 
		
// Create chart function
function createChart(target, date){
					
	$.ajax({
		url: 'ajax.php?a='+target+'&date='+date,
		dataType: 'json',
		success: function( jsonData ) {

			// If invalid data give feedback
			if(jsonData["ok"] == 0)
			{
				$('#message').text(jsonData["msg"]);
				$('#overlaySucces').fadeIn();
			}
			else
			{
				// Format data
				jsDate = jsonData["start"].split("-");
				year = jsDate[0];
				month = jsDate[1]-1;
				day = jsDate[2]-0;
				hour = jsDate[3]-0;
				minute = jsDate[4]-0;
				
				var start = (new Date(year, month, day, hour, minute)).getTime();
				var approximation = "average";

				if(target == 'day')
				{
					var title = 'Dagverbruik';
					var type = 'areaspline';
					var serieName = 'Watt';
					var yTitle = {
		                text: 'Watt',
		                margin: 40
		            };			
					var rangeSelector = false;
					var navScroll = true;
					var pointInterval = 60 * 1000;
					var tickInterval = null;
					var plotLines = null;											
					var buttons = [{
									type: 'hour',
									count: 1,
									text: '1u'
								}, {
									type: 'hour',
									count: 12,
									text: '12u'
								}, {
									type: 'day',
									count: 1,
									text: 'dag'
								}];
					var plotOptions = null;
				}
				else if(target == 'week')
				{
					var title = 'Weekverbruik';
					var type = 'areaspline';
					var serieName = 'Watt';
					var yTitle = {
		                text: 'Watt',
		                margin: 40
		            };					
					var rangeSelector = true;
					var navScroll = true;
					var pointInterval = 60 * 1000;
					var tickInterval = null;
					var plotLines = [{
						value: start + (24 * 60 * 60 * 1000),
						width: 1, 
						color: '#c0c0c0'
					},{
						value: start + (2 * 24 * 60 * 60 * 1000),
						width: 1, 
						color: '#c0c0c0'
					},{
						value: start + (3 * 24 * 60 * 60 * 1000),
						width: 1, 
						color: '#c0c0c0'
					},{
						value: start + (4 *24 * 60 * 60 * 1000),
						width: 1, 
						color: '#c0c0c0'
					},{
						value: start + (5 * 24 * 60 * 60 * 1000),
						width: 1, 
						color: '#c0c0c0'
					},{
						value: start + (6 * 24 * 60 * 60 * 1000),
						width: 1, 
						color: '#c0c0c0'
					}];										
					var buttons = [{
									type: 'hour',
									count: 1,
									text: '1u'
								}, {
									type: 'hour',
									count: 12,
									text: '12u'
								}, {
									type: 'day',
									count: 1,
									text: 'dag'
								}, {
									type: 'day',
									count: 7,
									text: 'week'
								}];
					var plotOptions = null;
				}
				else if(target == 'month')
				{
					var title = 'Maandverbruik';
					var type = 'column';
					var serieName = 'Watt';
					var yTitle = {
		                text: 'Watt',
		                margin: 40
		            };					
					var rangeSelector = false;
					var navScroll = false;
					var pointInterval = 60 * 1000;
					var tickInterval = null;
					var plotLines = [{
						value: start + (7 * 24 * 60 * 60 * 1000),
						width: 1, 
						color: '#c0c0c0'
					},{
						value: start + (14 * 24 * 60 * 60 * 1000),
						width: 1, 
						color: '#c0c0c0'
					},{
						value: start + (21 * 24 * 60 * 60 * 1000),
						width: 1, 
						color: '#c0c0c0'
					},{
						value: start + (28 *24 * 60 * 60 * 1000),
						width: 1, 
						color: '#c0c0c0'
					}];	
					var plotOptions =  {
			            cursor: 'pointer',
			            point: {
			                events: {
			                    click: function() {
			                        var dt = this.category;
			                        var target = "day";
			                        date = $.datepicker.formatDate("yy-mm-dd", new Date(dt));
			                        $('#datepicker').datepicker('setDate', date);
			                        $('#history').data('chart', target);
			                        console.log("target: " + target);
			                        showChart(target);
			                    }
			                }
			            }
				    };
				}
				else if(target == 'year')
				{
					var title = 'Jaarverbruik';
					var type = 'column';
					var serieName = 'Watt';
					var yTitle = {
		                text: 'Watt',
		                margin: 40
		            };					
					var rangeSelector = true;
					var navScroll = true;
					var pointInterval = 60 * 1000;
					var tickInterval = null;
					var plotLines = null;
					var buttons = [];
					var plotOptions =  {
			            cursor: 'pointer',
			            point: {
			                events: {
			                    click: function() {
			                        var dt = this.category;
			                        var target = "day";
			                        date = $.datepicker.formatDate("yy-mm-dd", new Date(dt));
			                        $('#datepicker').datepicker('setDate', date);
			                        $('#history').data('chart', target);
			                        console.log("target: " + target);
			                        showChart(target);
			                    }
			                }
			            }
				    };
				}
										
				
				// Parse values to integers
				data = jsonData["val"].split(",");
				for(var i=0; i<data.length; i++) { data[i] = parseFloat(data[i], 10); } 

				// Create the chart
				historychart = new Highcharts.StockChart({
					chart: {
						renderTo : 'history',
						type: type,			
						events: {
							load: function () {
	           
									var min = this.xAxis[0].getExtremes().min,
										max = this.xAxis[0].getExtremes().max;
									
									calculateRange(min,max);
									//requestLiveData();
									//getMeter();
							}
						}
					},			
					credits: {
						enabled: false
					},
					title: {
						text : title
					},	
					yAxis: {
						showFirstLabel: false,
						title: yTitle
					},
					xAxis: {
						type: 'datetime',
						tickInterval: tickInterval,
						plotLines: plotLines,
						events: {
							afterSetExtremes: function () {
								var min = this.min,
									max = this.max;
								
								calculateRange(min,max);
								//getMeter();
							}
						}
					},	
					rangeSelector: {
						selected: 3,
						enabled: rangeSelector,
						buttons: buttons
					},							
					navigator: {
						enabled: navScroll
					},									
					scrollbar: {
						enabled: navScroll
					},
					series : [{
						name : serieName,
						turboThreshold: 5000,
						data : data ,
						pointStart: start,
		            	pointInterval: pointInterval,
						dataGrouping: {
							approximation: approximation 
						},
						tooltip: {
							valueDecimals: 2
						}
					}],
	            	plotOptions: {
				        series: plotOptions
				    },
					dataGrouping: {
						enabled: false
					}
				});	
				
				calculate(target, date);
			}	
		},
		cache: false
	});
}		

function showChart(chart) {
	$('.chart').hide();
	$('.'+chart).show();
	
	$('.btn li').each(function(){
		$(this).removeClass('selected');
	});
	$('#'+chart).addClass('selected');
	$('#history').data('chart', chart);
	
	if(chart != 'live')
	{
		// Generate loading screen
		if(loadingEnabled)
		{
			historychart.showLoading();
		}
		else
		{
			loadingEnabled = true;
		}
		//createChart(chart, $('#datepicker').val());
		refreshData(chart, $('#datepicker').val());
	}
}
			
$(document).ready(function() {

	// Dialogs (alerts)
	$('#closeDialogSucces').click(function(){
		$('#overlaySucces').hide();
	});
	$('#closeDialogCredits').click(function(){
		$('#overlayCredits').hide();
	});

	// Refresh
	$('#refreshData').click(function(){
		refreshData(chart, $('#datepicker').val());
	});
		
	// Settings
	$('#showSettings').click(function(){
		$('#settingsOverlay').slideDown();
	});
	$('#hideSettings').click(function(){
		$('#settingsOverlay').slideUp(function(){
			var dualcnt = $('input[name=dualcount]:checked').val();
			if(dualcnt != $('#settingsOverlay').data('dualcount'))
			{
				$('input[name=dualcount]').not(':checked').attr('checked', true);
				if($('#settingsOverlay').data('dualcount') == 1)
				{
					$('.cpkwhlow').show();
				}
				else
				{
					$('.cpkwhlow').hide();
				}
			}		
		});		
	});
	
	$('input[name=dualcount]').change(function(){
		var dualcnt = $('input[name=dualcount]:checked').val();
		if(dualcnt == 1)
		{
			$('.cpkwhlow').show();
		}
		else
		{
			$('.cpkwhlow').hide();
		}
	});
		
	$('#saveSettings').click(function(){
		$.ajax({
			url: 'ajax.php?a=saveSettings',
			type: 'POST',
			dataType: 'json',
			data: $('#settingsOverlay form').serialize(),
			success: function( data ) {

				$('#settingsOverlay').slideUp('fast', function(){
					$('#settingsOverlay input[type=password]').val('');
				});
				
				if($('#settingsOverlay').data('dualcount') != $('input[name=dualcount]:checked').val())
				{
					$('#settingsOverlay').data('dualcount', $('input[name=dualcount]:checked').val());	
					var chart = $('#history').data('chart');
					//createChart(chart, $('#datepicker').val());					
				}
				$('#settingsOverlay').data('liveinterval', $('select[name=liveinterval]').val());
				$('#settingsOverlay').data('livelengte', $('select[name=livelengte]').val());						

				$('#message').text(data["msg"]);
				$('#overlaySucces').fadeIn();			
			},
		    error: function (xhr, ajaxOptions, thrownError) {
				$('#settingsOverlay').slideUp('fast', function(){
					$('#settingsOverlay input[type=password]').val('');
				});
				$('#message').text(thrownError);
				$('#overlaySucces').fadeIn();	
		         
		    }
		});			
		return false;
	});	

	$('#showCredits').click(function() {
		$('#settingsOverlay').slideUp('fast');
		$('#overlayCredits').fadeIn();	
	});
	
	// Show chart
	$('.showChart').click(function(){
		var chart = $(this).data('chart');
		showChart(chart);
	});
	
	
	//Highcharts options
	Highcharts.setOptions({
		global: {
			useUTC: false
		},	
		lang: {
			decimalPoint: ',',
			months: ['Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni', 'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December'],
			shortMonths: ['Jan', 'Feb', 'Mrt', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
			weekdays: ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag']
		}			
	});
	
	// Live chart
    chart = new Highcharts.Chart({
        chart: {
            renderTo: 'live',
            defaultSeriesType: 'areaspline',
            events: {
                load: function () {
					requestLiveData();
					getMeter();
				}
            }
        },         
		credits: {
			enabled: false
		},
		legend: {
			enabled: false
		},		      
        title: {
            text: 'Actueel verbruik'
        },
        xAxis: {
            type: 'datetime',
            tickPixelInterval: 150,
            minRange: $('#settingsOverlay').data('livelengte')
        },
        yAxis: {
			showFirstLabel: false,
            minPadding: 0.2,
            maxPadding: 0.2,
            title: {
                text: 'Watt',
                margin: 40
            }
        },
        series: [{
            name: 'Watt',
            data: []
        }],
		exporting: {
			enabled: false
		}		
    });  
	
		
	// Datepicker
	$('#datepicker').datepicker({
		inline: true,
		dateFormat: 'yy-mm-dd',
		maxDate: new Date(),
		showOn: 'focus',
		//changeMonth: true,
		//changeYear: true,	
		firstDay: 1,	
		monthNames: ['Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni', 'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December'],
        monthNamesShort: ['jan', 'feb', 'maa', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'],
        dayNames: ['zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag'],
        dayNamesShort: ['zon', 'maa', 'din', 'woe', 'don', 'vri', 'zat'],
        dayNamesMin: ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'],
		onSelect: function(date, inst){
			var target = $('#history').data('chart');			
			createChart(target, date);
		}
	});
			
		      
	$('a#next').click(function () {
		prev_next(1);
		return false;
	});

	$('a#previous').click(function () {
		prev_next(-1);
		return false;
	});
});

function prev_next(type) {
	var chart_type 	= $('li.selected .showChart').data('chart');
	var $picker 	= $("#datepicker");                                                                              
	var date 		= new Date($picker.datepicker('getDate'));

	if (type !== 1) type = -1;

	switch (chart_type) 
	{
	    case 'week': 
		    date.setDate(date.getDate() + (7 * type));
		    break;
	    case 'month':
	        date.setMonth(date.getMonth() + (1 * type));
	        break;
	    case 'year':
	        date.setFullYear(date.getFullYear() + (1 * type));
	        break;
	    default:
	        date.setDate(date.getDate() + (1 * type));
	        break;
	} 
	$picker.datepicker('setDate', date);
	var target = $('#history').data('chart');                                                                

	date = $picker.datepicker({ dateFormat: 'yy-mm-dd' }).val();

	createChart(target, date);                                           
}
