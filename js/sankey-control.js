window.onload=function(){   
var units = "connections";
 

 
var formatNumber = d3.format(",.0f"),    // zero decimal places
    format = function(d) { return formatNumber(d) + " " + units; },
    color = d3.scale.category20();
 
// append the svg canvas to the page
var svg = d3.select("#sankey-chart").append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom)
  .append("g")
    .attr("transform", 
          "translate(" + margin.left + "," + margin.top + ")");
 
// Set the sankey diagram properties
var sankey = d3.sankey()
    .nodeWidth(15)
    .nodePadding(10)
    .size([width, height]);
 
var path = sankey.link();
 
// load the data

//d3.json("sankeygreenhouse.json", function(error, graph) {
 
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
  var link = svg.append("g").selectAll(".linkpath")
	  .data(graph.links)
	 .enter()
	 .append("svg:a")
	  .attr("xlink:href", function(d) { 
						var ev = (d.source.type == 'hypothesis') ? 'evidence' : '';
						return (d.source.url+ev+'/'+d.target.type+'/'+d.target.id).replace(/\\/g,""); })
	  .attr("class", "linkpath")
	  .sort(function(a, b) { return b.dy - a.dy; });
			  
			  // add the link titles
  link.append("title")
        .text(function(d) {
      	return d.source.name + " → " + 
                d.target.name + "\n" + format(d.value); });
				
							
	link.append("path")
	  .attr("class", "link")
	  .attr("d", path)
	  .style("stroke-width", function(d) { return Math.max(1, d.dy); });
			  
 

 
// add in the nodes
  var node = svg.append("g").selectAll(".node")
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
      .style("fill", function(d) { 
		  return d.color = d.fill || color(d.name.replace(/ .*/, "")); })
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
	  .attr("class", function(d) { if (d.value === 0 ) return "hide"; })
      .attr("transform", null)
      .text(function(d) { return d.name; })
    .filter(function(d) { return d.x < width / 2; })
      .attr("x", 6 + sankey.nodeWidth())
      .attr("text-anchor", "start");
 
// the function for moving the nodes
  function dragmove(d) {
    d3.select(this).attr("transform", 
        "translate(" + (
        	   d.x = Math.max(0, Math.min(width - d.dx, d3.event.x))
        	) + "," + (
                   d.y = Math.max(0, Math.min(height - d.dy, d3.event.y))
            ) + ")");
    sankey.relayout();
    link.attr("d", path);
  }
//});

};