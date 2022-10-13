<html>
<head>
<meta charset="UTF-8">
<title>Narratives Search</title>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <link rel="stylesheet" type="text/css" href="../lib/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="../lib/narra.css" />
	<link rel="stylesheet" type="text/css" href="../storymaps/lib/narrativeVisualization.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.13.5/js/standalone/selectize.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.8.5/css/selectize.default.css" />
<script src="https://cdn.datatables.net/1.12.0/js/jquery.dataTables.min.js"></script> 
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.0/css/jquery.dataTables.min.css" />

<style>
table tr.odd {
    background-color: #F3F3F3 !important;
}
table tr .even {
    background-color: white !important;
}
</style>



<script>

 
	   
	$( document ).ready(function() {
		
		// write h1 title
		var browserUrl = new URL(window.location);
		var typeUrl = browserUrl.searchParams.get("type");
		
		$("#titleSearch").empty()
		$("#subtitle").empty()
		if(typeUrl==3){
			$("#titleSearch").append("Number of occurrences by entity")
			$("#subtitle").append("Selecting an entity you can find the number of occurrences of the selected entity in the story collection")
		} else if(typeUrl==2){
			$("#titleSearch").append("Select an entity to find related entities")
			$("#subtitle").append("Selecting an entity you can find the entities that co-occur with it in the events of the stories")
		} else if(typeUrl==1){
			$("#titleSearch").append("Events by entity")
			$("#subtitle").append("Selecting an entity you can find all the events of the story collection in which the entity appears")
		} else {
			$("#titleSearch").append("Narratives by entity")
			$("#subtitle").append("Selecting an entity you can find all the stories in which the entity appears")
		}
        
          
		//query for all entities and endpoint
		var url= "ENDPOINT HERE"		
		var q="PREFIX narra:<https://dlnarratives.eu/ontology#> PREFIX ecrm:<http://erlangen-crm.org/current/> PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> SELECT DISTINCT  ?entity ?label ?comment FROM <urn:x-arq:UnionGraph> WHERE { ?narration narra:hasEntity ?entity. ?entity rdfs:label ?label. ?entity rdfs:comment ?comment. } order by lcase(?label)"
			
		var query = url + encodeURIComponent(q) +"&output=json";
		
		//ajax to get all entites
		$.ajax({
			   type:"GET",
			   url:"query.php",
			   dataType:"JSON",
			   data:{quer: query},
			   success: function(data){
					console.log(data)
					var queryJson= JSON.parse(data.response)
					
					$("#entities").empty()
					Object.keys(queryJson.results.bindings).forEach(function(key) {
						
						if(queryJson.results.bindings[key].comment.value != ""){
							var descriptionEntity= " (" + queryJson.results.bindings[key].comment.value + ")" 
						} else {
							var descriptionEntity= ""
						}
						
						//append all entites in data list
						$("#entities").append("<option data-value='"+queryJson.results.bindings[key].entity.value+"' value='"+queryJson.results.bindings[key].label.value+descriptionEntity + "'></option>")
					
					});
					
					// onclick event on button to start others 2
					$("#submit").on("click", function(){
						
						searchNarration()
						
						
					})

				
			   },
			   error: function(d){
			   
			   }
		   });
	
	})
	

//start others query by entity and print tables	
function searchNarration(){
	
	//get selected entity
	var val = $('#selectedentities').val()
	var entity = $('#entities option').filter(function() {
			return this.value == val;
	}).data('value');
	
	
	var url= "ENDPOINT HERE"
	
	//differents queries based on url parameter
	var browserUrl = new URL(window.location);
	var typeUrl = browserUrl.searchParams.get("type");
	
	
	if(typeUrl==3){
		//numero totale di entità nelle narrazioni
		var q="PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> PREFIX narra:  <https://dlnarratives.eu/ontology#> PREFIX ecrm:   <http://erlangen-crm.org/current/> SELECT  (COUNT ( ?narrative) as ?totalEntities) ?narrative ?narrativeLabel ?link FROM <urn:x-arq:UnionGraph> WHERE { <"+entity+"> narra:isEntityOf ?event . ?event narra:partOfNarrative ?narrative . ?narrative rdfs:label ?narrativeLabel. ?narrative ecrm:P48_has_preferred_identifier ?link } group by $narrative ?narrativeLabel ?link"
	
	} else if(typeUrl==2){
		//tutte le cooccorrenze di entità negli eventi data un entità
		/* var q="PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> PREFIX narra:  <https://dlnarratives.eu/ontology#> PREFIX ecrm:   <http://erlangen-crm.org/current/> SELECT  ?narrativeLabel ?eventLabels ?labelEntity ?link ?event  FROM <urn:x-arq:UnionGraph> WHERE { ?event narra:hasEntity <"+entity+">. ?event narra:partOfNarrative ?narrative . ?entity narra:isEntityOf ?event . ?entity rdfs:label ?labelEntity. ?narrative ecrm:P48_has_preferred_identifier ?link. ?narrative rdfs:label ?narrativeLabel. ?event ecrm:P1_is_identified_by ?irititle. ?irititle ecrm:P3_has_note ?eventLabels . minus{ ?entity narra:isEntityOf ?event . filter( ?entity = <"+entity+"> ) } } order by ?eventLabels ?narrativeLabel ?labelEntity" */
		
		var q= "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> PREFIX narra:  <https://dlnarratives.eu/ontology#> PREFIX ecrm:   <http://erlangen-crm.org/current/> SELECT  ?narrativeLabel  (GROUP_CONCAT(?labelEntity ; separator=', ') as ?groupEntities)  ?eventLabels ?link ?event FROM <urn:x-arq:UnionGraph> WHERE { ?event narra:hasEntity <"+entity+">. ?event narra:partOfNarrative ?narrative . ?entity narra:isEntityOf ?event . ?entity rdfs:label ?labelEntity. ?narrative ecrm:P48_has_preferred_identifier ?link. ?narrative rdfs:label ?narrativeLabel. ?event ecrm:P1_is_identified_by ?irititle. ?irititle ecrm:P3_has_note ?eventLabels . minus{ ?entity narra:isEntityOf ?event . filter( ?entity = <"+entity+"> ) } } group by ?narrativeLabel ?eventLabels ?link ?event order by ?narrativeLabel"
	
	} else if(typeUrl==1){
		//tutti gli eventi data un entità
		var q="PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> PREFIX narra:  <https://dlnarratives.eu/ontology#> PREFIX ecrm:   <http://erlangen-crm.org/current/> SELECT  ?event  ?narrativeLabel ?link ?eventLabels FROM <urn:x-arq:UnionGraph> WHERE { <"+entity+"> narra:isEntityOf ?event . ?event narra:partOfNarrative ?narrative . ?event ecrm:P1_is_identified_by ?irititle. ?irititle ecrm:P3_has_note ?eventLabels . ?narrative rdfs:label ?narrativeLabel . ?narrative ecrm:P48_has_preferred_identifier ?link. }"
	
	
	} else {
		//tutte le narrazioni data un entità
		var q="PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> PREFIX narra:  <https://dlnarratives.eu/ontology#> PREFIX ecrm:   <http://erlangen-crm.org/current/> SELECT DISTINCT ?narrative ?narrativeLabel ?link FROM <urn:x-arq:UnionGraph> WHERE { <"+entity+"> narra:isEntityOf ?event . ?event narra:partOfNarrative ?narrative . ?narrative rdfs:label ?narrativeLabel. ?narrative ecrm:P48_has_preferred_identifier ?link } order by ?narrativeLabel"
	
	}

	var query = url + encodeURIComponent(q) +"&output=json";
	
	//ajax call for query elaborate
		$.ajax({
			   type:"GET",
			   url:"query.php",
			   dataType:"JSON",
			   data:{quer: query},
			   success: function(data){
					var queryJson= JSON.parse(data.response)
					
					//print different tables based on differente url parameter
					var browserUrl = new URL(window.location);
					var typeUrl = browserUrl.searchParams.get("type");
					
					var oderTable;
					
					
					$("#narrations table").empty()
					if(typeUrl==3){
						
						oderTable=[0, 'desc']
						
						
						$("#narrations table").append("<thead><tr><th>Number of entity</th><th>Storymap</th></tr></thead><tbody>")
						Object.keys(queryJson.results.bindings).forEach(function(key) {
							

							
							$("#narrations table").append("<tr> <td>"+queryJson.results.bindings[key].totalEntities.value+"</td> <td><a target='a_blank' href='"+queryJson.results.bindings[key].link.value+"'>"+queryJson.results.bindings[key].narrativeLabel.value+"</a></td> </tr>")
							

						});
					}else if(typeUrl==2){
						
						oderTable=[0, 'asc']
						var eventIri;
						var idEvent;
						
						
						$("#narrations table").append("<thead><tr><th>Storymap</th><th>Event</th><th>Entities</th></tr></thead><tbody>")
						Object.keys(queryJson.results.bindings).forEach(function(key) {
							
							eventIri= queryJson.results.bindings[key].event.value
							idEvent= eventIri.substring(eventIri.lastIndexOf('/') + 1)
							console.log(idEvent)
							
							/* $("#narrations table").append("<tr> <td><a target='a_blank' href='"+queryJson.results.bindings[key].link.value+"?start="+idEvent+"'>"+queryJson.results.bindings[key].eventLabels.value+"</a></td> <td>"+queryJson.results.bindings[key].narrativeLabel.value+"</td> <td>"+queryJson.results.bindings[key].labelEntity.value+"</td> </tr>") */

							$("#narrations table").append("<tr> <td>"+queryJson.results.bindings[key].narrativeLabel.value+"</td> <td><a target='a_blank' href='"+queryJson.results.bindings[key].link.value+"?start="+idEvent+"'>"+queryJson.results.bindings[key].eventLabels.value+"</a></td> <td>"+queryJson.results.bindings[key].groupEntities.value+"</td> </tr>")							
							

						});
					} else if(typeUrl==1){
						
						oderTable=[0, 'asc']
						var eventIri;
						var idEvent;
						
						
						$("#narrations table").append("<thead><tr><th>Storymap</th><th>Event</th></tr></thead><tbody>")
						Object.keys(queryJson.results.bindings).forEach(function(key) {
							
							eventIri= queryJson.results.bindings[key].event.value
							idEvent= eventIri.substring(eventIri.lastIndexOf('/') + 1)
							console.log(idEvent)
							
							$("#narrations table").append("<tr> <td>"+queryJson.results.bindings[key].narrativeLabel.value+"</td> <td><a target='a_blank' href='"+queryJson.results.bindings[key].link.value+"?start="+idEvent+"'>"+queryJson.results.bindings[key].eventLabels.value+"</a></td> </tr>")
						});
					
					
					} else {
						console.log(queryJson.results.bindings)
						oderTable=[0, 'asc']
						
						$("#narrations table").append("<thead><tr><th>Storymap</th></tr></thead><tbody>")
						Object.keys(queryJson.results.bindings).forEach(function(key){
							
							$("#narrations table").append("<tr><td> <a target='a_blank' href='"+queryJson.results.bindings[key].link.value+"'>"+queryJson.results.bindings[key].narrativeLabel.value+"</a>  </td></tr>")
						
						});
					
					}
					
					$("#narrations table").append("</tbody>")
					

					if ( $.fn.DataTable.isDataTable( '#dataTable' ) ) {
						$('#dataTable').DataTable().destroy();
					}   
					$('#dataTable').DataTable({
						order: [oderTable],
						oLanguage: {
							sSearch: "Filter Results:"
						}
					});
			   
			   },
			   error: function(d){
				
			   }
			   })

}
</script>





</head>

<body>

	<div id="menu">
		
		<div id="titleTable">
		<h1 id="titleSearch"></h1>
		</div>

		<?php 
			$user = $_GET["user"];
			$idNarra = $_GET["id"];
		?>
		
	
		<div class="otherVisual">
		  <button class="dropbtn">Other Searches</button>
		  <div class="otherVisual-content">
			<a href="?type=0<?php if(isset($user) && isset($idNarra)){ echo "&id=".$idNarra."&user=".$user; } ?>">Narratives by entity</a>
			<a href="?type=1<?php if(isset($user) && isset($idNarra)){ echo "&id=".$idNarra."&user=".$user; } ?>">Events by entity</a>
			<a href="?type=3<?php if(isset($user) && isset($idNarra)){ echo "&id=".$idNarra."&user=".$user; } ?>">Number of occurrences by entity</a>
			<a href="?type=2<?php if(isset($user) && isset($idNarra)){ echo "&id=".$idNarra."&user=".$user; } ?>">Select an entity to find related entities</a>
		  </div>
		</div>
		
		<div class="otherNarratives">
			<?php 

				if(isset($user) && isset($idNarra)){
					echo'<a href="https://dlnarratives.moving.d4science.org/storymaps/'.$user.'/'.$idNarra.'"><button class="dropbtn">Back to Map</button></a>';
				} else {
					echo'<button class="dropbtn" onclick="history.back()">Back</button>';
				}
			?>
		  

		</div>
		
	</div>


	<div id="contentSearch">
		
		<p id="subtitle" style="font-size:16px"></p>
		<input id="selectedentities" placeholder="type an entity" list="entities" name="entities"> <button id="submit">Search</button>
		<datalist id="entities">

		</datalist>
		
		

		<div id="narrations"><table id="dataTable"></table></div>

	</div>


</body>
</html>