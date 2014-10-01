// JavaScript Document

// Opposing bar charts
// http://jsfiddle.net/cuckovic/hwDt3/
/* edit these settings freely */

/*global d3: false, hyp_w: false, hyp_h: false, bar_data: false */

window.onload = function () {

	if (!window.d3) {
		return;
	}

	var topMargin = 20,
		labelSpace = 70,
		innerMargin = hyp_w/2+labelSpace,
		outerMargin = 15,
		gap = 2,
		dataRange = d3.max(bar_data.map(function(d) { return Math.max(d.barNeg, d.barPos) }));
		leftLabel = "Negative",
		rightLabel = "Positive";
	
		/* edit with care */
		var chartWidth = hyp_w - innerMargin - outerMargin,
			barWidth = hyp_h / bar_data.length,
			yScale = d3.scale.linear().domain([0, bar_data.length]).range([0, hyp_h-topMargin]),
			total = d3.scale.linear().domain([0, dataRange]).range([0, chartWidth - labelSpace]),
			commas = d3.format(",.0f");
		
		//console.log(data.length);
		
		/* main panel */
		var vis = d3.select("#vis").append("svg")
			.attr("width", hyp_w)
			.attr("height", hyp_h);
		
		/* barNeg label */
		vis.append("text")
		  .attr("class", "label")
		  .text(leftLabel)
		  .attr("x", hyp_w-innerMargin)
		  .attr("y", topMargin-10)
		  .attr("text-anchor", "end");
		
		/* barPos label */
		vis.append("text")
		  .attr("class", "label")
		  .text(rightLabel)
		  .attr("x", innerMargin)
		  .attr("y", topMargin-10);
		
		/* female bars and data labels */ 
		var bar = vis.selectAll("g.bar")
			.data(bar_data)
		  .enter().append("svg:a")
			.attr("xlink:href", function(d) { return d.url; })
			.attr("title", function(d) { return "Click to find out more about "+d.name; })
			.append("g")
			.attr("class", "bar")
			.attr("transform", function(d, i) {
			  return "translate(0," + (yScale(i) + topMargin) + ")"
			});
			
		bar.append("title").text(function(d) { return "Click to find out more about "+d.name;} );
		
		var wholebar = bar.append("rect")
			.attr("width", hyp_w)
			.attr("height", barWidth-gap)
			.attr("fill", "none")
			.attr("pointer-events", "all");
		
		var highlight = function(c) {
		  return function(d, i) {
			bar.filter(function(d, j) {
			  return i === j;
			}).attr("class", c);
		  };
		};
		
		bar
		  .on("mouseover", highlight("highlight bar"))
		  .on("mouseout", highlight("bar"));
		
		bar.append("rect")
			.attr("class", "femalebar")
			.attr("height", barWidth-gap);
		
		bar.append("text")
			.attr("class", "femalebar")
			.attr("dx", -3)
			.attr("dy", (barWidth-gap)/2+3)
			.attr("text-anchor", "end");
		
		bar.append("rect")
			.attr("class", "malebar")
			.attr("height", barWidth-gap)
			.attr("x", innerMargin);
		
		bar.append("text")
			.attr("class", "malebar")
			.attr("dx", 3)
			.attr("dy", (barWidth-gap)/2+3);
		
		/* names */
		bar.append("text")
			.attr("class", "shared")
			.attr("x", hyp_w/2)
			.attr("dy", (barWidth-gap)/2+3)
			.attr("text-anchor", "middle")
			.text(function(d) { return d.name; });
		

	
	refresh(bar_data);
	
	function refresh(bar_data) {
	  var bars = d3.selectAll("g.bar")
		  .data(bar_data);
	  bars.selectAll("rect.malebar")
		.transition()
		  .attr("width", function(d) { return total(d.barPos); });
	  bars.selectAll("rect.femalebar")
		.transition()
		  .attr("x", function(d) { return innerMargin - total(d.barNeg) - 2 * labelSpace; }) 
		  .attr("width", function(d) { return total(d.barNeg); });
	
	  bars.selectAll("text.malebar")
		  .text(function(d) { return commas(d.barPos); })
		.transition()
		  .attr("x", function(d) { return innerMargin + total(d.barPos); });
	  bars.selectAll("text.femalebar")
		  .text(function(d) { return commas(d.barNeg); })
		.transition()
		  .attr("x", function(d) { return innerMargin - total(d.barNeg) - 2 * labelSpace; });
	}

	window.jQuery && jQuery('.oer-chart-loading').hide();
}