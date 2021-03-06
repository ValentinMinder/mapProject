<!DOCTYPE html>
<html>

	<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />

		<style type="text/css">
			html { height: 100% }
			body { height: 100%; margin: 0; padding: 0 }
			#map-canvas { height: 100%; width: 100%; offset: -10 } 

		</style>
		<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=drawing&key=AIzaSyCxyaIp1mOe-MnCtZbtj2AyApHj6hoUJWM&sensor=true"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
	   
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">

		<!-- Optional theme -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css">

		<!-- Latest compiled and minified JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>

		<script src="flot/jquery.flot.js"></script>
		<script src="flot/jquery.flot.tooltip.min.js"></script>
		<script src="flot/jquery.flot.resize.js"></script>
		<script src="flot/jquery.flot.pie.js"></script>
		<script src="flot/jquery.flot.time.js"></script>

		<script type="text/javascript">

			<?php

				include_once 'Trip.php';
				include_once 'TripTracker.php';
				include_once "GPS.php";

				// Extract the trips from the tracker (more more dots)


				$dir = "/var/www/bonneaud/maps/gpx";

				$dh  = opendir($dir);
				while (false !== ($filename = readdir($dh))) 
				{

					if(!is_file($dir.'/'.$filename) || !strpos($filename, "gpx")) continue;

					$trip = simplexml_load_file($dir.'/'.$filename);

					$data = array();
					$data['year'] = $trip->metadata->YEAR;
					$data['medium'] = $trip->metadata->MEDIUM;
					$data['country'] = $trip->metadata->COUNTRY_EN;
					$data['area'] = $trip->metadata->AREA;
					$data['name'] = $trip->metadata->NAME;
					$data['duration'] = $trip->metadata->DURATION;
					$data['title'] = $trip->metadata->TITLE;
					$data['description'] = $trip->metadata->DESCRIPTION;
	
					// We extract the differents gps locations

					$stops = array();
					$tracks = $trip->trk->trkseg;
					$i = 1; 

					foreach ($tracks as $track)
					{
						foreach($track->trkpt as $pos) 
						{
							$stops[]=floatval($pos['lat']);
							$stops[]=floatval($pos['lon']);
							$stops[]=floatval($pos->ele);
							$i++;
						}
					}

					$t = new TripTracker($stops);
					$t->setData($data);
					$trips[] = $t;

				} 

				for($i=0;$i < count($trips);$i++)
				{
					// we print all the arrays of the gps positions and names
					$trip = $trips[$i];
					$trip->printItineraire();
					$trip->getElevation();
				}

			?>

			var trips = {};

			$( document ).ready(function() {

				<?php

					for($i=0;$i < count($trips);$i++)
					{
						// we print all the arrays of the gps positions and names
						$trip = $trips[$i];
						echo $trip->getObjectJS("trips");

					}

				?>
	
				$("#tripList").html("")
	
				$.each(trips, function(key, value) {
					var line = '<div class="panel panel-default">';
					line += '    <div class="panel-heading">';
					line += '        <h3 class="panel-title"><a href="#'+value.idRando+'" class="linkRando" idArray="'+value.idRando+'">'+value.title+' <i class="fa fa-caret-down" aria-hidden="true"></i></a></h3>';
					line += '    </div> ';
					line += '    <div id="'+value.idRando+'_div" class="panel-body panelRando hidden"> ';
					line += value.description+'<br/><br />';
					line += '        Country: '+value.country+'<br />';
					line += '        Length: '+value.length+'<br />';
					line += '        Duration: '+value.duration+'<br />';
					line += '        Medium: '+value.medium+'<br />';
					line += '    </div>';
					line += '</div>';
					$("#tripList").append(line)
				})

				$("#linkGeneral").click(function(e) {
					e.preventDefault();
					$(".panelRando").addClass("hidden")
					initialize()
				})			
    
				$(".linkRando").click(function(e) {
//					e.preventDefault();
					$(".panelRando").addClass("hidden")
					$("#"+$(this).attr("idArray")+"_div").removeClass("hidden")
					initializeRando(window[$(this).attr("idArray")], window["elevation_"+$(this).attr("idArray")])
				})

				var url = $(location).attr('href');
				var lm = url.split('#');
				if(lm.length > 1) {
					console.log(lm[1])
					$(".panelRando").addClass("hidden")
					$("#"+lm[1]+"_div").removeClass("hidden")
					initializeRando(window[lm[1]], window["elevation_"+lm[1]])
				} else {
					initialize()
				}
			})

			function initializeRando(trip, elevation) {

				$("#graphAltitude").removeClass("hidden")

				var mapOptions = {
					zoom: 12,
					mapTypeId: google.maps.MapTypeId.TERRAIN
				};
				var map = new google.maps.Map(document.getElementById("map-canvas"),mapOptions);

				traceTracker(trip, elevation, map);
	
				var bounds = new google.maps.LatLngBounds();

				for(i = 0; i < trip.length; i++)
				{
					bounds.extend(trip[i]);
				}
	
				map.fitBounds(bounds);
				map.panToBounds(bounds);
			}

			function initialize() {

				$(".panelRando").addClass("hidden")
				$("#graphAltitude").addClass("hidden")

				var mapOptions = {
					zoom: 3,
					mapTypeId: google.maps.MapTypeId.TERRAIN
				};
				var map = new google.maps.Map(document.getElementById("map-canvas"),mapOptions);
				var bounds = new google.maps.LatLngBounds();


				$.each(trips, function(key, value) {
					var marker = new google.maps.Marker({
						position: window[value.idRando][0],
						title:value.title
					});

					google.maps.event.trigger(marker, 'click');

					google.maps.event.addListener( marker, 'click', function(e) {
						$(".panelRando").addClass("hidden")
	                                        $("#"+value.idRando+"_div").removeClass("hidden")
        	                                initializeRando(window[value.idRando], window["elevation_"+value.idRando])
				        });
				   
					marker.setMap(map);
					bounds.extend(window[value.idRando][0]);

				})
	
				map.fitBounds(bounds);
				map.panToBounds(bounds);
			}

			var markerOnMap = null

			function traceTracker(flightPlanCoordinates, elevation, map) 
			{

				for(i = 0; i < flightPlanCoordinates.length-1; i++)
				{
					var flightPath = new google.maps.Polyline({
					path: [flightPlanCoordinates[i],flightPlanCoordinates[i+1]],
					geodesic: true,
					strokeColor: '#FF0000',
					strokeOpacity: 1.0,
					strokeWeight: 2,
					});

					flightPath.setMap(map); 
				}



				function euroFormatter(v, axis) {
					return v.toFixed(axis.tickDecimals)+"km" ;
				}

				position = "right"

				$.plot($("#flot-line-chart-multi"), [{
					data: elevation
				},], {
					xaxes: [ {}],
					yaxes: [{ }, {
						// align if we are to the right
						alignTicksWithAxis: position == "right" ? 1 : null,
						position: position,
						tickFormatter: euroFormatter
					}],
					legend: {
						position: 'sw'
					},
					colors: ["#1ab394"],
					grid: {
						color: "#999999",
						hoverable: true,
						clickable: true,
						tickColor: "#D4D4D4",
						borderWidth:0,
						hoverable: true //IMPORTANT! this is needed for tooltip to work,
					},
					tooltip: true,
					tooltipOpts: {
						content: "Altitude : %y m @ %x km",
						//xDateFormat: "%y-%0m-%0d",

						onHover: function(flotItem, $tooltipEl) {
							if(markerOnMap != null) markerOnMap.setMap(null)
								markerOnMap = new google.maps.Marker({
									position: flightPlanCoordinates[flotItem['dataIndex']],
									map: map
								});
						}
					}
				});

			}
		</script>
	</head>
	<body>
		<div class="row" style="height: 100%;">
			<div class="col-md-3" style="height: 100%; overflow-y: scroll; ">
				<button type="button" id="linkGeneral" class="btn btn-default" style="margin-left: 20px; margin-bottom: 15px; margin-top: 15px;">General view</button>
				<div style="margin-left: 20px;" id="tripList"></div>
	
			</div>
			<div class="col-md-9" style="height: 100%;">
				<div id="map-canvas" style="height: 80%;"></div>
				<div id="graphAltitude" style="height: 20%;">
					<div class="flot-chart" style="height: 100%;" >
                                		<div class="flot-chart-content" id="flot-line-chart-multi" style="height: 100%;" ></div>
                            		</div>
				</div>
			</div>
		</div>
	</body>
</html>
