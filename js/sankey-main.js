// JavaScript Document

/*global san: false, d3: false, MyAjax: false, SANKEY_MARGIN: false */

function renderSankey(country_slug, hyp_id) {

	if (!window.d3) {
		return;
	}

	var
		W = window,
		hyp_query = hyp_id ? ('&hyp_id=' + hyp_id) : '';

	d3.json(W.MyAjax.apiurl.replace('%s', 'hub.get_sankey_data') + 'country_slug=' + country_slug + hyp_query,
		function (graph) {

		var margin = SANKEY_MARGIN,
		width = W.SANKEY_WIDTH - margin.left - margin.right,
		height = W.SANKEY_HEIGHT - margin.top - margin.bottom;
		
		var units = "connections";
 
		var formatNumber = d3.format(",.0f"),    // zero decimal places
			format = function(d) { return formatNumber(d) + " " + units; },
			color = d3.scale.category20();
			
		// Set the sankey diagram properties
		var sankey = d3.sankey()
			.nodeWidth(10)
			.nodePadding(10)
			.size([width, height]);
			
		var spath = sankey.link();
		
		var nodeMap = {};
		graph.nodes.forEach(function(x) { nodeMap[x.name] = x; });
		graph.links = graph.links.map(function(x) {
		  return {
			source: nodeMap[x.source],
			target: nodeMap[x.target],
			value: x.value
		  };
		}); 
	 
		sankey
		  .nodes(graph.nodes)
		  .links(graph.links)
		  .layout(32);
		  
		  // add in the links
 
		san.selectAll('.link').remove();
		san.selectAll('.node').remove();
		san.selectAll('.santitle').remove();
			
		if(graph.links.length > 0){ 		
			var link = san.append("g").selectAll(".linkpath")
			  .data(graph.links)
			.enter()
			.append("svg:a")
			  .attr("xlink:href", function(d) { 
			  					console.log(d);
								var ev = (d.source.type === 'hypothesis') ? '/evidence' : '';
								return d.source.url+ev+'/'+d.target.type+'/'+d.target.id; })
			  .attr("class", "linkpath")
			  .sort(function(a, b) { return b.dy - a.dy; });
			
			// add the link titles
			link
			  .append("title")
				.text(function(d) {
				return d.source.name + " → " + 
						d.target.name + "\n" + format(d.value); });
			
			link 
			.append("path")
			  .attr("class", "link")
			  .attr("d", spath)
			  .style("stroke-width", function(d) { return Math.max(1, d.dy); });
			  //
			
			// add in the nodes
			var node = san.append("g").selectAll(".node")
			  .data(graph.nodes)
			.enter()
			  .append("g")
			  .attr("class", "node")
			  .attr("transform", function(d) { 
				  return "translate(" + d.x + "," + d.y + ")"; });
			/*.call(d3.behavior.drag()
			  .origin(function(d) { return d; })
			  .on("dragstart", function() { 
				  this.parentNode.appendChild(this); })
			  .on("drag", dragmove));*/
			
			// add the rectangles for the nodes
			node.append("svg:a")
			  .attr("xlink:href", function(d) { return d.url; })
			  .append("rect")
			  .attr("height", function(d) { return d.dy; })
			  .attr("width", sankey.nodeWidth())
			  .attr("class", function(d) { return d.id; })
			  .style("fill", function(d) {
				  //return d.color = d.fill || color(d.name.replace(/ .*/, "")); })
				  return d.color = d.fill || color(d.name.replace(new RegExp(" .*"), "")); })
			  .style("stroke", function(d) { 
				  return d3.rgb(d.color).darker(2); })
			.append("title")
			  .text(function(d) { 
				  return d.name + "\n" + format(d.value); });

			// add in the title for the nodes
			node.append("text")
			  .attr("x", -6)
			  .attr("y", function(d) { return d.dy / 2; })
			  .attr("dy", ".35em")
			  .attr("text-anchor", "end")
			  .attr("class", function(d) { if (d.value === 0) { return "hide"; } })
			  .attr("transform", null)
			  .text(function(d) { return d.name; })
			.filter(function(d) { return d.x < width / 2; })
			  .attr("x", 6 + sankey.nodeWidth())
			  .attr("text-anchor", "start");

		  var title = san.append("text")
			.attr("class", "santitle")
			.attr("x", 0)             
			.attr("y", -5)
			.attr("text-anchor", "start")  
			.style("font-size", "12px") 
			.style("font-style", "italic")
			.text("Evidence Flow - " + graph.title);
		}

		W.jQuery && W.jQuery(".oer-chart-loading").hide();
	})
	.header("Content-type", "application/x-www-form-urlencoded");
}
