/*
 *  COSIVERIF JAVASCRIPT WEB CLIENT
 */
    var rect_size = 30,
        rect_highlighted = 40,
        radius = rect_size / 2;
        radis_highlighted = (rect_size / 1.2);
    /* 
     * This object contains the d attribute of the path that later will come part of the force layout.
     * The shapes are defined so that the (0,0) is the center of each one. 
     */
    var shapes = {
        rect : "M " + -rect_size + " " + (-rect_size / 4) + " h " + 2 * rect_size + " v " + (rect_size / 2) + " h "+ (-2 * rect_size) + " z",
        rect_highlighted : "M " + -rect_highlighted + " " + (-rect_highlighted / 4) + " h " + 2 * rect_highlighted + " v " + (rect_highlighted / 2) + " h "+ (-2 * rect_highlighted) + " z",
        vertical_rect : "M " + (-rect_size / 4) + " " + -rect_size + " h " + (rect_size / 2) + " v " + 2 * rect_size + " h "      + (-rect_size / 2) + " z",
        circle : "M 0 0 m" + (-radius) +", 0 a " + radius + "," + radius + " 0 1,0 " + (radius * 2) +",0 " 
                        + "a " + radius + "," + radius + " 0 1,0 " + (-radius * 2) + ",0",
        circle_highlighted : "M 0 0 m" + (-radis_highlighted) +", 0 a " + radis_highlighted + "," + radis_highlighted + " 0 1,0 " + (radis_highlighted * 2) +",0 " + "a " + radis_highlighted + "," + radis_highlighted + " 0 1,0 " + (-radis_highlighted * 2) + ",0"
        };

    var element = {}
    
    var margin = {top: -5, right: -5, bottom: -5, left: -5},
        width = 960 - margin.left - margin.right,
        height = 500 - margin.top - margin.bottom,
        markerWidth = 8,
        markerHeight = 8,
        refX = radius + markerWidth;

    /*
     * @index_* = stores the index of the element for easier access
     */
    var nodes_index = {},
        index_links = {};
        
    var force = d3.layout.force()
        .size([width, height])
        .nodes([])
        .links([])
        .gravity(0)
        .linkDistance(300)
        .on("tick", tick);
                
    //~ var drag = force.drag().on("dragstart", dragstart);
    
    // We start to append elements to the original html.
    
    var svg = d3.select("#model_container").append("svg:svg")
        .attr("width", width)
        .attr("height", height)
        .attr("fill", "none")
        .attr("stroke", "black");

    // Per-type markers, as they don't inherit styles.
    svg.append("svg:defs").selectAll("marker")
        .data(["suit", "licensing", "resolved"])
        .enter().append("svg:marker")
        .attr("id", String)
        .attr("viewBox", "0 -5 10 10")
        .attr("refX", refX)
        .attr("markerWidth", markerWidth)
        .attr("markerHeight", markerHeight)
        .attr("orient", "auto")
        .append("svg:path")
        .attr("d", "M0,-5L10,0L0,5");
        

    var path = svg.append("svg:g").selectAll("path"),
        node = svg.append("svg:g").selectAll(".node"),
        circle = svg.append("svg:g").selectAll("g"),
        text = svg.append("svg:g").selectAll("g");
    
    /*
     * Update the model with the values stored in global WINDOW
     * from Lua Code
     */
    function updateModel(){
        
        //~ First we get all the values from the Lua world
        var count = window.count,
            model = window.model,
            keys = window.keys,
            var_type = window.var_type,
            type_place = window.type_place
            type_trans = window.type_transition
            type_arc = window.type_arc;
            
        // We get all the nodes
        var elem = {},
            pos,
            arc,
            key;
        
        var temp_links = [];
        var nodes = force.nodes(),
            links = force.links();
        
        //~ Read node data from the model.
        for(i = 1; i <= count; i++){
            k = keys.get(i);
            current_model = model.get(k);
            type = current_model.get(var_type);
            if(!type){
                continue;
            }
            //~ console.log("Printing key: " +k +" with type " + type.toString());
            //~ console.log("2 "+type_arc.toString());
            if(type_arc.toString() == type.toString()) {
                arc = {source : current_model.get('source').get('name'),
                        target : current_model.get('target').get('name'),
                        valuation : current_model.get('validation'),
                        type : typeToString(type_arc)};
                        
                temp_links.push(arc);
            } else {
                //~ Math.cos(x))
                marking = current_model.get('marking') ? current_model.get('marking') : '';
                highlighted = current_model.get('highlighted') ? current_model.get('highlighted') : '';
                
                elem = {name : current_model.get('name'),
                                type : typeToString(type), 
                                shape :  getShape(type, highlighted == 1),
                                marking : marking ? true : false,
                                px : current_model.get('x'),
                                py : current_model.get('y'),
                                highlighted : highlighted ? true : false,
                                fixed : true,
                                lua_node :current_model};
                
                if(undefined === nodes_index[current_model.get('name')]){
                    nodes.push(elem);
                    nodes_index[current_model.get('name')] = nodes.length - 1;
                } else {
                    pos = nodes_index[current_model.get('name')];
                    nodes[pos].name = elem.name;
                    nodes[pos].type = elem.type;
                    nodes[pos].shape = elem.shape;
                    nodes[pos].marking = elem.marking;
                    nodes[pos].px = elem.px;
                    nodes[pos].py = elem.py;
                    nodes[pos].highlighted = elem.highlighted;
                    nodes[pos].lua_node = elem.lua_node;
                }
            }
        }
        
        var source, target;
        
        /* Create a proper link array for the force layout
         */ 
        for(j = 0; j < temp_links.length; j++) {
            l = temp_links[j];
            
            source = nodes[nodes_index[l.source]];
            target = nodes[nodes_index[l.target]];
            if(undefined === index_links[source.name + ',' + target.name]){
                links.push({source: source, target: target, type: "licensing"});
                index_links[source.name + ',' + target.name] = links.length - 1;
            } else {
                pos = index_links[source.name + ',' + target.name];
                links[pos].source = source;
                links[pos].target = target;
                links[pos].type = "licensing";
            }
        }
        updateForceLayout();
 
        testRemoveElems();
        //~ var s = "20000:isdjfads200.2234234",
            //~ ry = /[0-9]*[.][0-9]+$/,
            //~ rx = /\d+/,
            //~ x_pos = rx.exec(s),
            //~ y_pos = ry.exec(s);
    }
    
    function updateForceLayout() {

        //~ We update the force layout data. 
        //~ force.links(links)
            //~ .nodes(nodes);
        path = path.data(force.links(), function(d){ return d.source.name +','+ d.target.name});
        
        path.enter().insert("svg:path", ".node");
        path.attr("class", function (d) {return "link " + d.type;})
            .attr("marker-end", function (d) {return "url(#" + d.type + ")";});
        path.exit().remove();
        
        node = node.data(force.nodes(), function (d) {return d.name;});
        node.enter().append("path");
        node.attr("class", "node")
            .attr("d", function(d){ return d.shape;})
            .attr("fill", function(d){ return d.highlighted ? "gold" : "#ccc"})
            .on("dblclick", dblclick)
            .on("click", click)
            .call(force.drag);
        
        node.exit().remove();
        
        circle = circle.data(force.nodes(), function (d) {return d.name;});
        circle.enter().append("circle")
                .attr("class", "token")
                .attr("r", radius/6)
                .attr("fill", "black")
                .call(force.drag);
                                
        circle.attr("visibility", function(d) {return d.marking ? "visible" : "hidden" })
        
        circle.exit().remove();

        text = text.data(force.nodes(), function (d) {return d.name;});
        text.enter().append("text")
            .attr("x", function(d){ return d.type == 'transition' ? 45 : 30})
            .attr("y", ".45em")
            .attr("size", 10);
        text.text(function(d) { return d.name; });
        text.exit().remove();
        
        force.start();
        
        return true;
    }
    
    function dblclick(d) {
        //~ d.lua_node.set("selected", true);
        //~ show();
        d3.select(this).classed("fixed", d.fixed = false);
    }
    
    function click(d){
        d3.select(this).classed("fixed", d.fixed = true);
    }
    
    //~ function dragstart(d) {
        //~ d3.select(this).classed("fixed", d.fixed = true);
        //~ force.start();
    //~ }

    function tick() {
        path.attr("d", function (d) {
            var dx = d.target.x - d.source.x,
                dy = (d.target.y - d.source.y),
                dr = Math.sqrt(dx * dx + dy * dy);
            /* 
             * TODO: Change this harcoded array
             */
            
            var link_shapes = {
                "licensing": "M" + d.source.x + "," + d.source.y + "L" + d.target.x + "," + d.target.y,
                "resolved": "M" + d.source.x + "," + d.source.y + "H" + d.target.x + " V " + d.target.y, 
                "elliptic": "M" + d.source.x + "," + d.source.y + "A" + dr + "," + dr + " 0 0,1 " + d.target.x + "," + d.target.y };
        
            return link_shapes[d.type];
        });

        node.attr("transform", transform);
        circle.attr("transform", transform);
        text.attr("transform", transform);
        
        function transform(d) {
            return "translate(" + d.x + "," + d.y + ")";
        }
    }
    
    function getShape(type, highlighted){
        var ret;
        if(highlighted)
            ret = typeToString(type) == 'transition' ? shapes.rect_highlighted : shapes.circle_highlighted;
        else
             ret = typeToString(type) == 'transition' ? shapes.rect : shapes.circle;
        return ret;
    }

    function typeToString(n){
        var ret = null;
        if(type_place.toString() == n.toString())
            ret = 'place';
        else if(type_trans.toString() == n.toString())
            ret = 'transition';
        else if(type_arc.toString() == n.toString())
            ret = 'arc';
        return ret;
    }
    
    function startUpdateTimer(){
        //~ setInterval(/*Some Function to check updates*/)
    }
    
    function testUpdateElems() {
        setTimeout(function() {
              links.pop();  
              nodes[0].marking = false;
              updateForceLayout();
            }, 3000);
    }
    
    function testRemoveElems() {
        setTimeout(function() {
            console.log('Removed called');
            var i = nodes_index['p_nil'];
            force.nodes().splice(i, 1);
            updateForceLayout();
            }, 3000);
    }
