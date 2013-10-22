<?php 
/*fdfdfd*/
require_once(dirname(__FILE__)."/../../../../../wp-load.php");
status_header(200);
?>
<!DOCTYPE html>
<!--
Global Oil Production & Consumption since 1965

index.html


@author			Timo Grossenbacher
@copyright		CC BY-NC-SA 2.0 2013
@license		MIT License (http://www.opensource.org/licenses/mit-license.php)

-->
<!--[if lt IE 7 ]><html class="ie ie6" lang="en"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="en"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="en"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!-->
<html lang="en">
    <!--<![endif]-->
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="description" content="oil consumption infographic ddj data-driven-journalism journalism datavisualization crude oil global oil production and consumption" />
        <meta name="og:image" content="http://labs.wnstnsmth.net/worldoil/img/worldoil.png" />
        <meta property="twitter:account_id" content="1512938576" />
        <link rel="image_src" href="http://labs.wnstnsmth.net/worldoil/img/worldoil.png" />
        
        <title>Global Oil Production & Consumption since 1965</title>

        <script src="lib/d3.v3.min.js" type="text/javascript" charset="utf-8"></script>
        <script src="lib/queue.v1.min.js" type="text/javascript" charset="utf-8"></script>
        <script src="lib/topojson.v1.min.js" type="text/javascript" charset="utf-8"></script>
        <script src="lib/colorbrewer.js" type="text/javascript" charset="utf-8"></script>
        <script src="lib/mootools-core-1.4.5.js" type="text/javascript" charset="utf-8"></script>
        <script src="lib/mootools-more-1.4.0.1.js" type="text/javascript" charset="utf-8"></script>
		
        <script src="src/control.js" type="text/javascript" charset="utf-8"></script>

        <!-- main script -->
        <script src="src/main.js" type="text/javascript" charset="utf-8"></script>

        <link rel="stylesheet" type="text/css" href="css/base.css" />
        <link rel="stylesheet" type="text/css" href="css/skeleton.css" />
        <link rel="stylesheet" type="text/css" href="css/styles.css" />
        <!--[if lt IE 9]>
        <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->

        <!-- Mobile-spezifische Metatags
        ================================================== -->
        <!--<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">-->

    </head>
    <body>
        <div id="loading">
            Loading...
        </div>
        <div id="control">
            <div id="text">
                <a href="https://twitter.com/share"
                class="twitter-share-button"
                data-url="http://labs.wnstnsmth.net/worldoil"
                data-text="Global Oil Production & Consumption |"
                data-via="wnstns"
                data-count="horizontal"
                data-size="medium"
                data-dnt="true">Tweet</a>
                <p>
                    Instead of writing yet another paper, I handed in this visualization for the <a href="http://www.leru.org/index.php/public/activities/other-activities/bright/">LERU Bright 2013 Student Conference</a>
                    which will be held in August in Freiburg, Germany. This year's conference topic is "Energy Transition in the 21st Century" and I am part of the "Dependencies" working group.
                </p>
                <p>
                    This <a href="http://www.monde-diplomatique.de/pm/.atlas3">"Atlas der Globalisierung"</a>-inspired visualization, based on <a href="http://www.bp.com/en/global/corporate/about-bp/statistical-review-of-world-energy-2013.html">very recent data by BP</a>, allows the reader to quickly grasp the temporal and spatial differences in oil consumption and production.
                    On one hand, during certain periods of history, some nations consumed almost as much oil as the rest of the world together. On the other hand, the data of the last ten years show a growing divergence between consumption and production.
                    After all, I hope this work makes clear that nations are heavily interdependent when it comes to oil - the main driver of our global economy.
                </p>
                <p>
                    Crafted with <a href="http://d3js.org">D3.js</a>.
                </p>
                <p>
                    If you are interested in coming projects, follow me on <a href="http://twitter.com/wnstns">Twitter</a>.
                </p>
            </div>
            <br>
        </div>
        <div id="container">

            <header>
                <h1>Global Oil Production & Consumption since 1965</h1>
            </header>
            <div id="impressum">
                <small>
                    <p>Tested in latest versions of Firefox, Chrome, Safari, and Internet Explorer. A minimal screen resolution of 1600 x 900px is recommended.</p>
                    <p>Note that the original data set does not consider all the countries of the world. For some countries, values are missing for a certain time period (e.g. for Russia/former UDSSR). </p>
                    <p>"Production" includes crude oil, shale oil, oil sands and NLGs, "consumption" also includes fuel ethanol and biodiesel, refinery fuel and loss.</p>
                    <strong>Author</strong>:
                    <br/>
                    Timo Grossenbacher (BSc in Geography, University of Zurich)
                    <br/>
                    <strong>Sources</strong>:
                    <br/>
                    Geodata: <a href="https://github.com/mbostock/topojson/blob/master/examples/world-110m.json">mbostock/topojson</a>
                    <br/>
                    Data: <a href="http://www.bp.com/en/global/corporate/about-bp/statistical-review-of-world-energy-2013.html">BP Statistical Review of World Energy 2013</a>
               </small>    
            </div>

        </div>
        <script type="text/javascript">
            window.addEvent('domready', function() {
                init();
                //constructControlPanel('Global Oil Production & Consumption');
                
            });
        </script>

    </body>
</html>