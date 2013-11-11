<style>
.evidence-map .node rect {
  /*cursor: move;*/
  fill-opacity: .8;
  shape-rendering: crispEdges;
}
 
.evidence-map .node text {
  pointer-events: none;
  text-shadow: 0 1px 0 #fff;
  font-size:11px;
  font-family: "Open Sans", Helvetica, Arial, "Nimbus Sans L", sans-serif;
}
.evidence-map .node text.hide {
  display:none;
}
 
.evidence-map .link {
  fill: none;
  stroke: #000;
  stroke-opacity: .2;
}
 
.evidence-map .link:hover {
  stroke-opacity: .5;
}
</style>
<div id="evidence_hub_overview" class="wrap"> 
	<div id="content" class="evidence-map"><h2>Evidence Summary</h2><?php echo do_shortcode('[evidence_map]'); ?></div>
	<!-- <div class="evidence_hub_header" style="text-align:center"><a href="http://oerresearchhub.org/" title="OER Research Hub" rel="home"> <img src="http://oerresearchhub.files.wordpress.com/2013/07/cropped-oer_700-banner-2.jpg" width="513" alt=""> </a></div> -->    
</div>