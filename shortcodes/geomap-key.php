<?php
/**
 * Key for [geomap] shortcode (class-general_geomap.php).
 *
 * @link  js/markercluster/leaflet.markercluster-src.js#L620-626  Cluster boundaries.
 */
?>

<div id="key" class="evidence-map-key geomap-key key-type-<?php echo $type ?>">
<h3>Key</h3>
<ul>
  <li class="marker cluster"><div class="marker-cluster marker-cluster-small" ><div><span>2</span></div></div>
    Small cluster: <?php if('evidence'==$type):?>limited evidence<?php else:?>a limited number of items<?php endif;?> <small>(green)</small>
  <li class="marker cluster"><div class="marker-cluster marker-cluster-medium"><div><span>21</span></div></div>
    Medium cluster: more than 20 items <?php if('evidence'==$type):?>of evidence<?php endif;?> <small>(orange)</small>
  <li class="marker cluster"><div class="marker-cluster marker-cluster-large" ><div><span>101</span></div></div>
    Large cluster: more than 100 items <?php if('evidence'==$type):?>of evidence<?php endif;?> <small>(red)</small>

  <li class="marker evidence evidence-pos"     > Positive evidence <small>(orange pin)</small>
  <li class="marker evidence evidence-neutral" > Neutral evidence, mixed evidence or no polarity given <small>(blue pin)</small>
  <?php #<li class="marker evidence"             > Polarity not given <small>(blue pin)</small> ?>
  <li class="marker evidence evidence-neg"     > Negative evidence <small>(grey pin)</small>

  <li class="marker project"> Project <small>(dark blue pin)</small>

  <li class="marker policy policy-international"> International policy <small>(orange square marker)</small>
  <li class="marker policy policy-local"     > Local/institutional policy
  <li class="marker policy policy-national"  > National policy
  <li class="marker policy policy-regional"  > Regional policy
</ul>
</div>
