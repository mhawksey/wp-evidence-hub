<?php
/**
 * Key for [geomap] shortcode (class-general_geomap.php).
 */
?>
<div id="key" class="evidence-map-key geomap-key key-type-<?php echo $type ?>">
<h3>Key</h3>
<ul>
  <li class="marker cluster"><div class="marker-cluster marker-cluster-small" ><div><span>2</span></div></div> Small cluster: limited evidence <small>(green)</small>
  <li class="marker cluster"><div class="marker-cluster marker-cluster-medium"><div><span>21</span></div></div> Medium cluster: more than 20 pieces of evidence <small>(orange)</small>
  <li class="marker cluster"><div class="marker-cluster marker-cluster-large" ><div><span>201</span></div></div> Large cluster  <small>(red)</small>

  <li class="marker evidence evidence-pos"     > Positive evidence <small>(orange pin)</small>
  <li class="marker evidence evidence-neutral" > Neutral/mixed evidence <small>(blue pin)</small>
  <li class="marker evidence"                  > Polarity not given <small>(blue pin)</small>
  <li class="marker evidence evidence-neg"     > Negative evidence <small>(grey pin)</small>

  <li class="marker project"> Project <small>(dark blue pin)</small>

  <li class="marker policy policy-international"> International policy <small>(orange square marker)</small>
  <li class="marker policy policy-local"     > Local/institutional policy
  <li class="marker policy policy-national"  > National policy
  <li class="marker policy policy-regional"  > Regional policy
</ul>
</div>
