var constructControlPanel = function(apptitle) {
    // enable twitter
    ! function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (!d.getElementById(id)) {
            js = d.createElement(s);
            js.id = id;
            js.src = "//platform.twitter.com/widgets.js";
            fjs.parentNode.insertBefore(js, fjs);
        }
    }(document, "script", "twitter-wjs");
    var controlDiv = document.getElement('#control');
    controlDiv.setStyle('display', 'block');
    var closeButton = new Element('a', {
        id : 'close',
        title : 'close',
        events : {
            'click' : function() {
                controlDiv.setStyle('display', 'none');
            }
        },
        text : 'x'
    });
    var dragButton = new Element('a', {
        id : 'move',
        title : 'move',
        events : {
            'click' : function(event) {
                event.stop();
            }
        }
    });
    
    var textDiv = controlDiv.getElement('div#text').dispose();
    var title = new Element('h1', {

    });
    var homelink = new Element('a', {
        href : 'http://wnstnsmth.net?pk_campaign=labs',
        text : 'Winston Smith'
    });
    var subtitle = new Element('h2', {
        text : 'Labs',
        'class' : 'website-title'
    });
    var apptitle = new Element('h2', {
        text : apptitle,
        styles : {
            display : 'block'
        }
    });
    title.grab(homelink);
    controlDiv.grab(title);
    controlDiv.grab(subtitle);
    controlDiv.grab(apptitle);
    controlDiv.grab(textDiv);
    controlDiv.grab(closeButton);
    controlDiv.grab(dragButton);
    document.body.grab(controlDiv);

    // make it draggable
    var controlDrag = new Drag(controlDiv, {handle: controlDiv.getElement('#move')});
    
}

