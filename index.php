<html>

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="utf-8" http-equiv="encoding">
    <title>Xcos</title>

    <!-- Loads and initializes the library -->
    <script type="text/javascript" src="jquery/jquery-1.8.2.js"></script>
    <script type="text/javascript" src="mxClient.min.js"></script>
    <script type="text/javascript" src="editor/mxDefaultKeyHandler.js"></script>
    <script type="text/javascript" src="handler/mxKeyHandler.js"></script>
    <script type="text/javascript" src="jquery/farbtastic.js"></script>

    <link rel="stylesheet" href="jquery/farbtastic.css" type="text/css" />
    <link rel="stylesheet" href="jquery/jquery-ui.css">

    <script type="text/javascript" src="details.js"></script>
    <script type="text/javascript" src="setup.js"></script>
    <script type="text/javascript" src="json2.js"></script>
    <script type="text/javascript">
        function main(container, outline, toolbar, sidebar, status) {
            // Checks if the browser is supported
            if (!mxClient.isBrowserSupported()) {
                // Displays an error message if the browser is not supported.
                mxUtils.error('Browser is not supported!', 200, false);
            } else {
                // If connect preview is not moved away then getCellAt is used to detect the cell under
                // the mouse if the mouse is over the preview shape in IE (no event transparency), ie.
                // the built-in hit-detection of the HTML document will not be used in this case.
                mxConnectionHandler.prototype.movePreviewAway = false;
                mxConnectionHandler.prototype.waypointsEnabled = true;
                mxGraph.prototype.resetEdgesOnConnect = false;

                // Enables guides
                mxGraphHandler.prototype.guidesEnabled = true;

                // Alt disables guides
                mxGuide.prototype.isEnabledForEvent = function(evt) {
                    return !mxEvent.isAltDown(evt);
                };

                // Enables snapping waypoints to terminals
                mxEdgeHandler.prototype.snapToTerminals = true;

                // Assigns some global constants for general behaviour, eg. minimum
                // size (in pixels) of the active region for triggering creation of
                // new connections, the portion (100%) of the cell area to be used
                // for triggering new connections, as well as some fading options for
                // windows and the rubberband selection.
                mxConstants.MIN_HOTSPOT_SIZE = 16;
                mxConstants.DEFAULT_HOTSPOT = 1;

                // Workaround for Internet Explorer ignoring certain CSS directives
                if (mxClient.IS_QUIRKS) {
                    document.body.style.overflow = 'hidden';
                    new mxDivResizer(container);
                    new mxDivResizer(outline);
                    new mxDivResizer(toolbar);
                    new mxDivResizer(sidebar);
                    new mxDivResizer(status);
                }

                // Creates a wrapper editor with a graph inside the given container.
                // The editor is used to create certain functionality for the
                // graph, such as the rubberband selection, but most parts
                // of the UI are custom in this example.
                var editor = new mxEditor();
                var graph = editor.graph;
                var model = graph.getModel();

                /*
                    Maverick
                    The following variable 'diagRoot' serves as the root element for the entire
                    diagram.
                */
                var diagRoot = new XcosDiagram(null, model, null);

                graph.setPanning(true);
                graph.setConnectable(true);
                graph.setConnectableEdges(true);
                graph.setDisconnectOnMove(false);
                graph.foldingEnabled = false;

                // Disable highlight of cells when dragging from toolbar
                graph.setDropEnabled(false);

                // Centers the port icon on the target port
                graph.connectionHandler.targetConnectImage = true;

                // Does not allow dangling edges
                graph.setAllowDanglingEdges(false);

                // Sets the graph container and configures the editor
                editor.setGraphContainer(container);

                // Disables built-in context menu
                mxEvent.disableContextMenu(document.body);

                // Configures automatic expand on mouseover
                graph.panningHandler.autoExpand = true;

                /*
                  @jiteshjha, @pooja
        				  Overrides mxGraphModel.getStyle to return a specific style
        				  for edges that reflects their target terminal.
                */

                graph.model.getStyle = function(cell) {
                    var style = null;
                    if (cell != null) {
                        // Get style for the recently created mxCell.
                        style = mxGraphModel.prototype.getStyle.apply(this, arguments);
                        // If the mxCell is an edge and if it's a fully formed edge
                        if (this.isEdge(cell) && cell.source != null) {
                            var target = this.getTerminal(cell, false);
                            if (target != null) {
                                /* cell.name attribute defines the link name
                                   so that it can be parsed in the XML during
                                   XSLT transformation.
                                */
                                var cellSource = cell.source;
                                while (cellSource.isEdge() == true) {
                                    cellSource = cellSource.source;
                                }
                                if (cellSource.value == "ExplicitOutputPort" || cellSource.value == "ExplicitInputPort") {
                                    if (style == null) {
                                        style = 'ExplicitLink' + ';';
                                    }
                                    cell.name = "ExplicitLink";
                                } else if (cellSource.value == "ImplicitOutputPort" || cellSource.value == "ImplicitInputPort") {
                                    if (style == null) {
                                        style = 'ImplicitLink' + ';';
                                    }
                                    cell.name = "ImplicitLink";
                                } else if (cellSource.value == "CommandPort" || cellSource.value == "ControlPort") {
                                    if (style == null) {
                                        style = 'CommandControlLink' + ';';
                                    }
                                    cell.name = "CommandControlLink";
                                }
                            }
                        }
                    }
                    return style;
                };


                // Creates a right-click menu
                graph.panningHandler.factoryMethod = function(menu, cell, evt) {

                    if (cell != null) {

                        if (cell.value == "ExplicitInputPort" || cell.value == "ExplicitOutputPort" || cell.value == "CommandPort" || cell.value == "ControlPort") {

                        } else if (cell.isEdge() == true) // @ToDo: Pooja: Different edge value cases.
                        {

                            menu.addItem('Delete', 'images/delete2.png', function() {
                                editor.execute('delete');
                            });
                            var edgeformat = menu.addItem('Format', null, null);

                            menu.addItem('Border Color', 'images/draw-brush.png', function() {
                                showColorWheel(graph, cell, 'edgeStrokeColor');

                            }, edgeformat);
                            menu.addItem('Text and Text Font', 'images/edit.png', function() {
                                showTextEditWindow(graph, cell);
                            }, edgeformat);
                            menu.addItem('Text Color', 'images/edit.png', function() {
                                showColorWheel(graph, cell, 'edgeTextColor');
                            }, edgeformat);

                        } else {
                            menu.addItem('Block Parameters...', 'images/gear.gif', function() {
                                showPropertiesWindow(graph, cell);
                            });

                            menu.addItem('Cut', 'images/cut.png', function() {
                                editor.execute('cut');
                            });
                            menu.addItem('Copy', 'images/copy.png', function() {
                                editor.execute('copy');
                            });
                            menu.addItem('Delete', 'images/delete2.png', function() {
                                editor.execute('delete');
                            });

                            menu.addItem('Selection to superblock', 'images/superblock.png', function() {
                                // @ToDo: Pooja: Functionality to be put.
                            });
                            var format = menu.addItem('Format', null, null);

                            menu.addItem('Rotate', 'images/rotate.png', function() {
                                editor.execute('rotateCustom');
                            }, format);
                            menu.addItem('Border Color', 'images/draw-brush.png', function() {
                                showColorWheel(graph, cell, 'vertexStrokeColor');

                            }, format);
                            menu.addItem('Fill Color', 'images/edit.png', function() {
                                showColorWheel(graph, cell, 'vertexFillColor');
                            }, format);
                            menu.addItem('Details', null, function() {
                                // @ToDo: Pooja: Functionality to be put.
                            });
                        }
                    } else {
                        menu.addItem('Undo', 'images/undo.png', function() {
                            editor.execute('undo');
                        });
                        menu.addItem('Redo', 'images/redo.png', function() {
                            editor.execute('redo');
                        });
                        menu.addItem('Paste', 'images/paste.png', function() {
                            editor.execute('paste');
                        });

                        menu.addItem('Select all', 'images/selectall.png', function() {
                            editor.execute('selectAll');
                        });

                        /*
                            Maverick
                            Added one more parameter to the setContext function.
                        */
                        menu.addItem('Set Context', null, function() {
                            showSetContext(graph, diagRoot);
                        });

                        /*
                            Maverick
                            Added one more parameter to the setContext function.
                        */
                        menu.addItem('Setup', 'images/setup.png', function() {
                            showSetupWindow(graph, diagRoot);
                        });


                        menu.addItem('Zoom In', 'images/zoom_in.png', function() {
                            editor.execute('zoomIn');
                        });
                        menu.addItem('Zoom Out', 'images/zoom_out.png', function() {
                            editor.execute('zoomOut');
                        });
                        menu.addItem('Diagram background...', null, function() {
                            showColorWheel(graph, cell, 'bgColor');
                        });
                    }

                };

                //var config = mxUtils.load('config/editor-commons.xml').getDocumentElement();
                var config = mxUtils.load('config/keyhandler-commons.xml').getDocumentElement();
                editor.configure(config);


                /*
                  For a new edge on the graph, check if that edge satisfies one of the port constraints.
                */
                graph.addEdge = function(edge, parent, source, target, index) {

                    var edgeSource = source;

                    // If the source of the edge is also an edge, find the port.
                    while (edgeSource.isEdge() == true) {
                        edgeSource = edgeSource.source;
                    }

                    // If the edge violates any port constraints, return null.
                    if (!((edgeSource.getEdgeCount() == 0 && edgeSource.isVertex() &&
                                target.getEdgeCount() == 0 && target.isVertex()) ||
                            (edgeSource.getEdgeCount() <= 1 && source.isEdge()))) {
                        alert("Port is already connected, please select an please select an unconnected port or a valid link");
                    } else if (edgeSource.value == "ExplicitOutputPort" && target.value != "ExplicitInputPort") {
                        alert("Explicit data output port must be connected to explicit data input port");
                    } else if (edgeSource.value == "ExplicitInputPort" && target.value != "ExplicitOutputPort") {
                        alert("Explicit data input port must be connected to explicit data output port");
                    } else if (edgeSource.value == "ImplicitOutputPort" && target.value != "ImplicitInputPort") {
                        alert("Implicit data output port must be connected to implicit data input port");
                    } else if (edgeSource.value == "ImplicitInputPort" && target.value != "ImplicitOutputPort") {
                        alert("Implicit data input port must be connected to implicit data output port");
                    } else if (edgeSource.value == "CommandPort" && target.value != "ControlPort") {
                        alert("Command port must be connected to control port");
                    } else if (edgeSource.value == "ControlPort" && target.value != "CommandPort") {
                        alert("Control port must be connected to command port");
                    } else {
                        // If the edge is legit, return the edge.
                        return mxGraph.prototype.addEdge.apply(this, arguments);
                    }

                    return null;
                }

                // Shows a "modal" window when double clicking a vertex.
                graph.dblClick = function(evt, cell) {
                    // Do not fire a DOUBLE_CLICK event here as mxEditor will
                    // consume the event and start the in-place editor.
                    if (this.isEnabled() &&
                        !mxEvent.isConsumed(evt) &&
                        cell != null &&
                        this.isCellEditable(cell)) {
                        if (!this.isHtmlLabel(cell)) {
                            this.startEditingAtCell(cell);
                        } else {
                            /*
                            var content = document.createElement('div');
                            content.innerHTML = this.convertValueToString(cell);
                            showModalWindow(this, 'Properties', content, 400, 300);
                            */
                            if (cell.isVertex() == true) {
                                showPropertiesWindow(graph, cell);
                            }
                        }
                    }

                    // Disables any default behaviour for the double click
                    mxEvent.consume(evt);
                };

                // Returns a shorter label if the cell is collapsed and no
                // label for expanded groups
                graph.getLabel = function(cell) {
                    var tmp = mxGraph.prototype.getLabel.apply(this, arguments); // "supercall"
                    if (this.isCellLocked(cell)) {
                        // Returns an empty label but makes sure an HTML
                        // element is created for the label (for event
                        // processing wrt the parent label)
                        return '';
                    } else if (this.isCellCollapsed(cell)) {
                        var index = tmp.indexOf('</h1>');
                        if (index > 0) {
                            tmp = tmp.substring(0, index + 5);
                        }
                    }
                    return tmp;
                }

                // Disables HTML labels for swimlanes to avoid conflict
                // for the event processing on the child cells. HTML
                // labels consume events before underlying cells get the
                // chance to process those events.
                //
                // NOTE: Use of HTML labels is only recommended if the specific
                // features of such labels are required, such as special label
                // styles or interactive form fields. Otherwise non-HTML labels
                // should be used by not overidding the following function.
                // See also: configureStylesheet.
                graph.isHtmlLabel = function(cell) {
                    return !this.isSwimlane(cell);
                }

                graph.getTooltipForCell = function(cell) {
                    var text = null;
                    if (cell.isVertex() == true && cell.isConnectable() == false) {
                        var name = cell.value.getAttribute('blockElementName');
                        var cellvar = cell.blockInstance.instance.details();

                        // If cell is a block or ports
                        if (cell.source == null && cell.target == null) {
                            if (cell.connectable) { // Cell is a Port
                                // @ToDo: Port Number
                                text = 'Style : ' + cell.style + "\n";
                            } else { //Cell is a block
                                // @ToDo: Block Name, Simulation, Flip, Mirror
                                // @ToDo: Number of Input, Output, Control, Command Ports
                                var inputPort, outputPort, controlPort, commandPort;
                                if (cellvar.model.in.height == null) {
                                    inputPort = 0;
								}
                                else {
                                    inputPort = cellvar.model.in.height;
								}
                                if (cellvar.model.out.height == null) {
                                    outputPort = 0;
                                }
								else {
                                    outputPort = cellvar.model.out.height;
								}
                                if (cellvar.model.evtin.height == null) {
                                    controlPort = 0;
								}
                                else {
                                    controlPort = cellvar.model.evtin.height;
								}
                                if (cellvar.model.evtout.height == null) {
                                    commandPort = 0;
								}
                                else {
                                    commandPort = cellvar.model.evtout.height;
								}
                                var geometry = cell.getGeometry();
                                text = 'Block Name : ' + cell.value.getAttribute('blockElementName') + "\n" +
                                    'Simulation : ' + cell.value.getAttribute('simulationFunctionName') + "\n" +
                                    'UID : ' + cell.id + "\n" +
                                    'Style : ' + cell.style + "\n" +
                                    'Flip : ' + getData(cellvar.graphics.flip)[0] + "\n" +
                                    'Mirror : false' + "\n" +
                                    'Input Ports : ' + inputPort + "\n" +
                                    'Output Ports : ' + outputPort + "\n" +
                                    'Control Ports : ' + controlPort + "\n" +
                                    'Command Ports : ' + commandPort + "\n" +
                                    'x : ' + geometry.x + "\n" +
                                    'y : ' + geometry.y + "\n" +
                                    'w : ' + geometry.width + "\n" +
                                    'h : ' + geometry.height + "\n";
                            }
                        }
                    }
                    return text;
                };

                // Create XML tags!
                // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
                // https://jgraph.github.io/mxgraph/docs/js-api/files/model/mxCell-js.html
                // Uncomment this block to see XML tags work
                /*graph.convertValueToString = function(cell)
                {
                  if (mxUtils.isNode(cell.value))
                  {
                    return cell.getAttribute('label', '');
                  }
                };*/

                var cellLabelChanged = graph.cellLabelChanged;
                graph.cellLabelChanged = function(cell, newValue, autoSize) {
                    if (mxUtils.isNode(cell.value)) {
                        // Clones the value for correct undo/redo
                        var elt = cell.value.cloneNode(true);
                        elt.setAttribute('label', newValue);
                        newValue = elt;
                    }

                    cellLabelChanged.apply(this, arguments);
                };

                // Enables new connections
                graph.setConnectable(true);

                // Adds all required styles to the graph (see below)
                configureStylesheet(graph);

                // Adds sidebar icons.
                addIcons(graph, sidebar);

                // Creates a new DIV that is used as a toolbar and adds
                // toolbar buttons.
                var spacer = document.createElement('div');
                spacer.style.display = 'inline';
                spacer.style.padding = '8px';

                // Defines a new export action
                editor.addAction('toggle', function(editor, cell) {
                    var toggle = document.getElementById("toggleBlocks");
                    var button = document.getElementById("toggle");
                    toggle.click();
                    button.innerHTML = '';
                    if (toggle.innerHTML == 'Expand All') {
                        createButtonImage(button, 'images/navigate_plus.png');
                    } else if (toggle.innerHTML == 'Collapse All') {
                        createButtonImage(button, 'images/navigate_minus.png');
                    }
                    var titleName = document.createTextNode(toggle.innerHTML);
                    button.appendChild(titleName);
                });


                // @jiteshjha, @pooja
                /*
                   On selection and deletion of any block, 'deleteBlock'
                   function deletes all the associated edges with that block.
                   Used Preorder traversal for edges.
                */
                editor.addAction('deleteBlock', function(editor, cell) {
                    var cells = [];
                    var selectionCells = graph.getSelectionCells();
                    for (var k = 0; k < selectionCells.length; k++) {
                        var portCount = selectionCells[k].getChildCount();
                        cells.push(selectionCells[k]);
                        // Finds all the port with edges of the selected cell, and calls getEdgeId() for
                        // each edge object of that port.
                        for (var i = 0; i < portCount; i++) {
                            var edgeCount = selectionCells[k].getChildAt(i).getEdgeCount();
                            if (edgeCount != 0) {
                                getEdgeId(selectionCells[k].getChildAt(i));

                                for (var j = 0; j < edgeCount; j++) {
                                    var edgeObject = selectionCells[k].getChildAt(i).getEdgeAt(j);
                                    getEdgeId(edgeObject);
                                }
                            }
                        }
                    }


                    /* getEdgeId() find all the associated edges from an edge.
                     Pushes the object of that edge into an array of mxCell objects.
                     */
                    function getEdgeId(edgeObject) {
                        var cellStack = [];
                        if (edgeObject != null && edgeObject.isEdge() == true) {
                            cellStack.push(edgeObject);
                            while (cellStack.length != 0) {
                                var tempEdgeObject = cellStack.pop();
                                if (tempEdgeObject.edge == true && (cells.indexOf(tempEdgeObject) == -1)) {
                                    cells.push(tempEdgeObject);
                                }
                                for (var j = 0; j < tempEdgeObject.getEdgeCount(); j++) {
                                    cellStack.push(tempEdgeObject.getEdgeAt(j));
                                }
                            }
                        }
                    }

                    // The mxCells to be deleted are first highlighted,
                    // and then the selection is deleted in a single go.
                    graph.getSelectionModel().setCells(cells);
                    editor.execute('delete');
                });

                addToolbarButton(editor, toolbar, 'toggle', 'Expand All', 'images/navigate_plus.png');
                toolbar.appendChild(spacer.cloneNode(true));

                addToolbarButton(editor, toolbar, 'cut', 'Cut', 'images/cut.png');
                addToolbarButton(editor, toolbar, 'copy', 'Copy', 'images/copy.png');
                addToolbarButton(editor, toolbar, 'paste', 'Paste', 'images/paste.png');

                toolbar.appendChild(spacer.cloneNode(true));

                addToolbarButton(editor, toolbar, 'delete', '', 'images/delete2.png');
                addToolbarButton(editor, toolbar, 'undo', '', 'images/undo.png');
                addToolbarButton(editor, toolbar, 'redo', '', 'images/redo.png');
                toolbar.appendChild(spacer.cloneNode(true));

                addToolbarButton(editor, toolbar, 'show', 'Show', 'images/camera.png');
                addToolbarButton(editor, toolbar, 'print', 'Print', 'images/printer.png');

                toolbar.appendChild(spacer.cloneNode(true));


                /*
                    Maverick
                    This method is used for loading the stylesheet from the file.
                    Reference: http://www.w3schools.com/xsl/xsl_client.asp
	        */

                function loadXMLDoc(filename) {
                    if (window.ActiveXObject) {
                        xhttp = new ActiveXObject("Msxml2.XMLHTTP");
                    } else {
                        xhttp = new XMLHttpRequest();
                    }
                    xhttp.open("GET", filename, false);
                    try {
                        xhttp.responseType = "msxml-document"
                    } catch (err) {}
                    xhttp.send("");
                    return xhttp.responseXML;
                }


                /*
				Maverick
                The Export buttons in toolbar call this function with varying
                arguments.
                The third argument is used to decide which button is being
                pressed.
				exportXML : 2 arguments
				exportXcos: 3 arguments
                */
                function displayXMLorXcos() {
                    var textarea = document.createElement('textarea');
                    textarea.style.width = '400px';
                    textarea.style.height = '400px';
                    var enc = new mxCodec(mxUtils.createXmlDocument());
                    /*var array=[],key;
                    for (key in diagRoot.model.cells) {
                        
                        if(diagRoot.model.cells[key].connectable == false)
                        {
                            array.push(diagRoot.model.cells[key].inst);
                            diagRoot.model.cells[key].inst=null;
                        }
                    }*/
                    var node = enc.encode(diagRoot);

                    var str = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" + mxUtils.getPrettyXml(node);

                    textarea.value = str;
                    /*var j = 0;
                    for (key in diagRoot.model.cells) {
                        
                        if(diagRoot.model.cells[key].connectable == false)
                        {
                            diagRoot.model.cells[key].inst=array[j++];
                        }
                    }*/
                    if (arguments[2] == null) {
                        showModalWindow(graph, 'XML', textarea, 410, 440);
                    } else {

                        return mxUtils.getPrettyXml(node);
                    }
                }

                // Defines a new export action
                editor.addAction('exportXML', function(editor, cell) {
                    //Only two parameters passed here.
                    displayXMLorXcos(editor, cell);
                });

                /* Maverick
                 Reference: http://www.w3schools.com/xsl/xsl_client.asp
                */

                editor.addAction('exportXcos', function(editor, cell) {
                    //Mind the 3 parameters.
                    var xmlFromExportXML = displayXMLorXcos(editor, cell, true);
                    if (xmlFromExportXML == null) alert('First create the XML file.');
                    else {

                        var xml = mxUtils.parseXml(xmlFromExportXML);

                        var xsl = loadXMLDoc("finalmodsheet.xsl");

                        xsltProcessor = new XSLTProcessor();
                        xsltProcessor.importStylesheet(xsl);
                        resultDocument = xsltProcessor.transformToDocument(xml);


                        var textarea = document.createElement('textarea');
                        textarea.style.width = '400px';
                        textarea.style.height = '400px';
                        /*
                            Maverick
                            Using resultDocument.documentElement to remove an additional tag "<#document>" created by the XSLTProcessor.
                        */
                        var str = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n\n" + mxUtils.getPrettyXml(resultDocument.documentElement);

                        textarea.value = str.replace(/\n\n/g, "\n");
                        showModalWindow(graph, 'Xcos', textarea, 410, 440);
                    }
                });

                addToolbarButton(editor, toolbar, 'exportXML', 'Export XML', 'images/export1.png');

                addToolbarButton(editor, toolbar, 'exportXcos', 'Export Xcos', 'images/export1.png');

                // Adds toolbar buttons into the status bar at the bottom
                // of the window.

                addToolbarButton(editor, status, 'zoomIn', '', 'images/zoom_in.png', true);
                addToolbarButton(editor, status, 'zoomOut', '', 'images/zoom_out.png', true);
                addToolbarButton(editor, status, 'actualSize', '', 'images/view_1_1.png', true);
                addToolbarButton(editor, status, 'fit', '', 'images/fit_to_size.png', true);

                // Creates the outline (navigator, overview) for moving
                // around the graph in the top, right corner of the window.
                var outln = new mxOutline(graph, outline);

                // To show the images in the outline, uncomment the following code
                //outln.outline.labelsVisible = true;
                //outln.outline.setHtmlLabels(true);

                // Fades-out the splash screen after the UI has been loaded.
                var splash = document.getElementById('splash');
                if (splash != null) {
                    try {
                        mxEvent.release(splash);
                        mxEffects.fadeOut(splash, 100, true);
                    } catch (e) {

                        // mxUtils is not available (library not loaded)
                        splash.parentNode.removeChild(splash);
                    }
                }

                // Handles cursor keys - guides.html
                var nudge = function(keyCode) {
                    if (!graph.isSelectionEmpty()) {
                        var dx = 0;
                        var dy = 0;
                        if (keyCode == 37) {
                            dx = -5;
                        } else if (keyCode == 38) {
                            dy = -5;
                        } else if (keyCode == 39) {
                            dx = 5;
                        } else if (keyCode == 40) {
                            dy = 5;
                        }
                        graph.moveCells(graph.getSelectionCells(), dx, dy);
                    }
                };
                // Transfer initial focus to graph container for keystroke handling
                // graph.container.focus();
                // Handles keystroke events
                var keyHandler = new mxKeyHandler(graph);
                keyHandler.bindKey(37, function() {
                    nudge(37);
                });
                keyHandler.bindKey(38, function() {
                    nudge(38);
                });
                keyHandler.bindKey(39, function() {
                    nudge(39);
                });
                keyHandler.bindKey(40, function() {
                    nudge(40);
                });

                // Starts connections on the background in wire-mode
                var connectionHandlerIsStartEvent = graph.connectionHandler.isStartEvent;
                graph.connectionHandler.isStartEvent = function(me) {
                    return connectionHandlerIsStartEvent.apply(this, arguments);
                };

                // Avoids any connections for gestures within tolerance except when in wire-mode
                // or when over a port
                var connectionHandlerMouseUp = graph.connectionHandler.mouseUp;
                graph.connectionHandler.mouseUp = function(sender, me) {
                    if (this.first != null && this.previous != null) {
                        var point = mxUtils.convertPoint(this.graph.container, me.getX(), me.getY());
                        var dx = Math.abs(point.x - this.first.x);
                        var dy = Math.abs(point.y - this.first.y);

                        if (dx < this.graph.tolerance && dy < this.graph.tolerance) {
                            // Selects edges in non-wire mode for single clicks, but starts
                            // connecting for non-edges regardless of wire-mode
                            if (this.graph.getModel().isEdge(this.previous.cell)) {
                                this.reset();
                            }

                            return;
                        }
                    }

                    connectionHandlerMouseUp.apply(this, arguments);
                };

                mxEvent.disableContextMenu(container);

                // @Adhitya: Add focus to a mxCell
                if (mxClient.IS_NS) {
                    mxEvent.addListener(graph.container, 'mousedown', function(evt) {
                        if (!graph.isEditing()) {
                            graph.container.setAttribute('tabindex', '-1');
                            graph.container.focus();
                        }
                    });
                }

            }
        };

        /*
          @jiteshjha
          styleToObject(style) converts style string into an object.
          Format : First item in the object will be 'default: linkStyle',
          and the rest of items will be of the style 'mxConstants:value'
        */

        function styleToObject(style) {

            var defaultStyle = style.substring(0, style.indexOf(';'));
            var styleObject = {
                "default": defaultStyle
            };
            var remainingStyle = style.substring(style.indexOf(';') + 1);

            /*
              remainingStyle is the string without the default style.
              For every key:value pair in the string,
              extract the key(string before '=') and the value
              (string before ';'), set the key:value pair into styleObject
              and remainingStyle is set to a string without the key:value pair.
            */
            while (remainingStyle.length > 0) {
                var indexOfKey = remainingStyle.indexOf('=');
                var key = remainingStyle.substring(0, indexOfKey);
                remainingStyle = remainingStyle.substring(indexOfKey + 1);
                var indexOfValue = remainingStyle.indexOf(';');
                var value = remainingStyle.substring(0, indexOfValue);
                styleObject[key] = value;
                remainingStyle = remainingStyle.substring(indexOfValue + 1);
            }

            return styleObject;
        }

        /*
          @jiteshjha
          styleToObject(style) converts the object back to the style string.
        */
        function objectToStyle(object) {
            var style = "";
            for (var key in object) {
                if (key.toString() == "default") {
                    style += object[key] + ';';
                } else {
                    style += (key + '=' + object[key] + ';');
                }
            }
            return style;
        }

        /*
            Maverick
            The following function is used to define a tag for entire diagram.
            We can set context, model and setup parameters for the entire diagram
            using this function.
        */


        function XcosDiagram(context, model, attributes) {
            this.context = context;
            this.model = model;
            this.finalIntegrationTime = attributes;
        }


        /*
          @jiteshjha, @pooja
          setContext dialog box
          Includes a set context instruction text and input text area.
        */

        /*
            Maverick
            Added 'diagRoot' parameter.
        */
        function showSetContext(graph, diagRoot) {

            // Create basic structure for the form
            var content = document.createElement('div');
            content.setAttribute("id", "setContext");

            // Add Form
            var myform = document.createElement("form");
            myform.method = "";
            myform.setAttribute("id", "formProperties");

            // Add set context string
            var descriptionSetContext = document.createElement("div");
            descriptionSetContext.innerHTML = "You may enter here scilab instructions to define symbolic parameters used in block definitions using Scilab instructions. These instructions are evaluated once confirmed(i.e. you click on OK and every time the diagram is loaded)";
            descriptionSetContext.setAttribute("id", "descriptionSetContext");
            myform.appendChild(descriptionSetContext);

            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);

            // input text area
            var textareaSetContext = document.createElement("textarea");
            textareaSetContext.setAttribute("id", "textareaSetContext");

            myform.appendChild(textareaSetContext);

            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);

            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);

            // Button - Submit
            var btn = document.createElement("button");
            btn.innerHTML = 'Ok';
            btn.type = "button";
            btn.name = "submit";
            btn.setAttribute("id", "buttonSetContext");

            var contextValue = handleContext("get");

            var displayValue = "";

            /*
                Maverick
                Modified the for loop because only requirement was to
                traverse the array of 'contextValue' and not all the
                elements of it.
            */
            for (var i = 0; i < contextValue.length; i++) {
                displayValue += contextValue[i] + "\n";
            }
            if (contextValue != "") {
                textareaSetContext.value = displayValue;
            } else {
                textareaSetContext.value = "";

            }

            // Executes when button 'btn' is clicked
            btn.onclick = function() {

                var input = document.getElementById('textareaSetContext').value;

                /*
                    Maverick
                    Code to extract context parameter values from the text area
                    containing the input.
                */
                var contextValues = [];
                var i = 0,
                    temp = "";
                for (i = 0; i < input.length; i++) {
                    if (input[i] == '\n') {
                        if (temp != "") {
                            contextValues.push(temp);
                        }
                        temp = "";
                        continue;
                    }
                    temp += input[i];
                }
                if (temp != "") {
                    contextValues.push(temp);
                }

                diagRoot.context = contextValues;
                diagRoot.context.scilabClass = "String[]";
                handleContext("set", contextValues);
                wind.destroy();
            };

            myform.appendChild(btn);
            content.appendChild(myform);
            var wind = showModalWindow(graph, 'Set Context', content, 450, 350);
        };

        function showPropertiesWindow(graph, cell) {

            var name = cell.getAttribute('blockElementName');
            var defaultProperties = cell.blockInstance.instance.get(); //window[name]("get");
            /*{
                nbr_curves: ["Number of curves", 1],
                clrs: ["color (>0) or mark (<0)", [1, 2, 3, 4, 5, 6, 7, 13]],
                siz: ["line or mark size", [1, 1, 1, 1, 1, 1, 1, 1]],
                win: ["Output window number (-1 for automatic)", -1],
                wpos: ["Output window position", [-1, -1]],
                wdim: ["Output window sizes", [-1, -1]],
                vec_x: ["Xmin and Xmax", [-15, 15]],
                vec_y: ["Ymin and Ymax", [-15, 15]],
                vec_z: ["Zmin and Zmax", [-15, 15]],
                param3ds: ["Alpha and Theta", [50, 280]],
                N: ["Buffer size", 2]
            };*/

            //var defaultProperties=window["CONST_m"]("get");

            // Create basic structure for the form
            var content = document.createElement('div');
            content.setAttribute("id", "contentProperties");

            // Heading of content
            var heading = document.createElement('h2');
            heading.innerHTML = "Set Scope Parameters";
            heading.id = "headingProperties"
            content.appendChild(heading);

            // Add Form
            var myform = document.createElement("form");
            myform.method = "post";
            myform.id = "formProperties";

            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);

            for (var key in defaultProperties) {
                if (defaultProperties.hasOwnProperty(key)) {

                    // Input Title
                    var fieldName = defaultProperties[key];
                    var namelabel = document.createElement('label');
                    namelabel.innerHTML = defaultProperties[key][0];
                    myform.appendChild(namelabel);

                    // Input
                    var input = document.createElement("input");
                    input.name = key;
                    input.value = defaultProperties[key][1];
                    input.setAttribute("id", key.toString());
                    input.setAttribute("class", "fieldInput");
                    myform.appendChild(input);

                    // Line break
                    var linebreak = document.createElement('br');
                    myform.appendChild(linebreak);

                    // Line break
                    var linebreak = document.createElement('br');
                    myform.appendChild(linebreak);
                }
            }

            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);

            // Button - Submit
            var btn = document.createElement("button");
            btn.innerHTML = 'Submit';
            btn.type = "button";
            btn.name = "submit";

            // Executes when button 'btn' is clicked
            btn.onclick = function() {
                var propertiesObject = {
                    id: cell.id
                };

                for (var key in defaultProperties) {
                    if (defaultProperties.hasOwnProperty(key)) {
                        propertiesObject[key] = document.getElementById(key.toString()).value;
                    }
                }
                var details = cell.blockInstance.instance.set(propertiesObject); //window[name]("set",cell.value,propertiesObject);
                var enc = new mxCodec();
                var node = enc.encode(details);
                node.setAttribute('label', getData(details.exprs)[0]);
                cell.value = node;
                /*
                    Maverick
                    We have changed the value of the cell, but the change won't be reflected
                    unless the graph is refreshed.
                */
                graph.refresh();
                wind.destroy();
            };
            myform.appendChild(btn);

            // Button - Reset
            var btn = document.createElement("button");
            btn.innerHTML = 'Reset';
            btn.type = "button";
            btn.name = "submit";
            btn.id = "resetButtonProperties";
            btn.onclick = function() {
                // Reset
                for (var key in defaultProperties) {
                    if (defaultProperties.hasOwnProperty(key)) {
                        var element = document.getElementById(key.toString());
                        element.value = defaultProperties[key][1];
                    }
                }
            };

            myform.appendChild(btn);
            // Base height without fields : 135 px
            height = 135 + 26 * defaultProperties.length + 15;

            content.appendChild(myform);
            var wind = showModalWindow(graph, 'Properties', content, 450, height);
        };

        /*
          @jiteshjha
          Creates a dialog box related to the edge label properties.
          The properties implemented are : edge label, label fontStyle,
          label fontSize, label fontStyle.
        */


        function showTextEditWindow(graph, cell) {
            var fontFamilyList = {
                "Arial": 0,
                "Dialog": 1,
                "Verdana": 2,
                "Times New Roman": 3
            }
            var defaultProperties = {
                text: ["Text", "text"],
                fontFamily: ["Font Family", fontFamilyList],
                fontSize: ["fontSize", 20]
            };

            var style = graph.getModel().getStyle(cell);
            var styleObject = styleToObject(style);
            if ('fontSize' in styleObject) {
                defaultProperties['fontSize'][1] = styleObject['fontSize'];
            }
            if (cell.value != "") {
                defaultProperties['text'][1] = cell.value;
            }

            // Create basic structure for the form
            var content = document.createElement('div');
            content.setAttribute("id", "contentProperties");

            // Heading of content
            var heading = document.createElement('h2');
            heading.innerHTML = "Text and Text Font";
            heading.id = "headingProperties"
            content.appendChild(heading);

            // Add Form
            var myform = document.createElement("form");
            myform.method = "post";
            myform.id = "formProperties";

            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);

            for (var key in defaultProperties) {
                if (defaultProperties.hasOwnProperty(key)) {

                    // Input Title
                    var fieldName = defaultProperties[key];
                    var namelabel = document.createElement('label');
                    namelabel.innerHTML = defaultProperties[key][0];
                    myform.appendChild(namelabel);

                    if (key == "fontFamily") {
                        //Here we create a "select" element (a drop down list).
                        var newList = document.createElement("select");
                        newList.style.cssText = "float:right";
                        newList.setAttribute("id", key.toString());
                        var dropdownItems = defaultProperties[key][1];

                        for (var item in dropdownItems) {
                            if (dropdownItems.hasOwnProperty(item)) {
                                option = document.createElement('option');
                                option.value = item;
                                option.text = item;
                                option.setAttribute("id", item);
                                newList.appendChild(option);
                            }
                        }

                        var selectedFontFamily = 0;
                        var styleObject = styleToObject(style);
                        if ('fontFamily' in styleObject) {
                            selectedFontFamily = styleObject['fontFamily'];
                        }
                        newList.selectedIndex = dropdownItems[selectedFontFamily];
                        myform.appendChild(newList);
                    } else {
                        var input = document.createElement("input");
                        input.name = key;
                        input.value = defaultProperties[key][1];
                        input.setAttribute("id", key.toString());
                        input.setAttribute("class", "fieldInput");
                        myform.appendChild(input);
                    }
                    // Line break
                    var linebreak = document.createElement('br');
                    myform.appendChild(linebreak);

                    // Line break
                    var linebreak = document.createElement('br');
                    myform.appendChild(linebreak);
                }
            }

            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);

            /*
              changeFontStyle function sets the style for given fontStyle and toggles with the active class
              for "set" type, and toggles with the active class for "get" type.
            */
            function changeFontStyle(type, graph, cell, button, bit) {
                var style = graph.getModel().getStyle(cell);
                var trigger = document.getElementById(button);
                var styleObject = styleToObject(style);
                var previousValue = 1;
                if ('fontStyle' in styleObjesct) {
                    previousValue = styleObject['fontStyle'];

                    // To get a bit mask:
                    var mask = 1 << bit; // Get the 1st element

                    if (type == "get") {
                        // toggle the bit
                        previousValue ^= mask;
                        trigger.classList.toggle(button);
                        styleObject['fontStyle'] = previousValue;
                        style = objectToStyle(styleObject);
                        graph.getModel().setStyle(cell, style);
                    } else if (type == "set") {
                        if ((previousValue & mask) != 0) {
                            trigger.classList.toggle(button);
                        }
                    }
                }
            }

            // Button - Bold
            var btn = document.createElement("button");
            btn.innerHTML = 'Bold';
            btn.setAttribute("id", "boldButton");
            btn.type = "button";
            btn.name = "submit";
            btn.onclick = function() {
                changeFontStyle("get", graph, cell, 'boldButton', 0);
            }
            myform.appendChild(btn);

            // Button - Italics
            var btn = document.createElement("button");
            btn.innerHTML = 'Italic';
            btn.setAttribute("id", "italicButton");
            btn.type = "button";
            btn.name = "submit";
            btn.onclick = function() {
                changeFontStyle("get", graph, cell, 'italicButton', 1);
            }
            myform.appendChild(btn);

            // Button - Underline
            var btn = document.createElement("button");
            btn.innerHTML = 'Underline';
            btn.setAttribute("id", "underlineButton");
            btn.type = "button";
            btn.name = "submit";
            btn.onclick = function() {
                changeFontStyle("get", graph, cell, 'underlineButton', 2);
            }
            myform.appendChild(btn);

            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);

            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);

            // Button - Submit
            var btn = document.createElement("button");
            btn.innerHTML = 'Submit';
            btn.type = "button";
            btn.name = "submit";

            // Executes when button 'btn' is clicked
            btn.onclick = function() {
                var propertiesObject = {
                    id: cell.id
                };
                for (var key in defaultProperties) {
                    if (defaultProperties.hasOwnProperty(key)) {
                        propertiesObject[key] = document.getElementById(key.toString()).value;
                    }
                }
                var style = graph.getModel().getStyle(cell);
                var styleObject = styleToObject(style);
                styleObject['fontSize'] = propertiesObject['fontSize'];
                styleObject['fontFamily'] = propertiesObject['fontFamily'];
                style = objectToStyle(styleObject);
                graph.getModel().setStyle(cell, style);
                graph.getModel().setValue(cell, propertiesObject['text']);
                wind.destroy();
            };
            myform.appendChild(btn);

            // Base heights without fields : 135 px
            height = 135 + 26 * defaultProperties.length + 15;
            content.appendChild(myform);
            var wind = showModalWindow(graph, 'Text and Text font', content, 450, height);

            /*
              @jiteshjha
              If any fontStyle(Bold, Italic, Underline) has already been implemented
              for the selected edge label, add the respective active class to that button.
            */

            if ('fontStyle' in styleObject) {
                changeFontStyle("set", graph, cell, 'boldButton', 0);
                changeFontStyle("set", graph, cell, 'italicButton', 1);
                changeFontStyle("set", graph, cell, 'underlineButton', 2);
            }
        };
        /*
          @jiteshjha, @pooja
          showSetupWindow dialog box
        */

        /*
            Maverick
            Added 'diagRoot' parameter.
        */
        function showSetupWindow(graph, diagRoot) {

            /*
                Maverick
                Added one more element in the list for each key to be used in the <XcosDiagram>
                tag.
            */

            var defaultProperties = setup("get");

            // Create basic structure for the form
            var content = document.createElement('div');
            content.setAttribute("id", "contentProperties");

            // Heading of content
            var heading = document.createElement('h2');
            heading.innerHTML = "Setup";
            heading.id = "headingProperties"
            content.appendChild(heading);

            // Add Form
            var myform = document.createElement("form");
            myform.method = "post";
            myform.id = "formProperties";

            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);

            for (var key in defaultProperties) {
                if (defaultProperties.hasOwnProperty(key)) {

                    // Input Title
                    var fieldName = defaultProperties[key];
                    var namelabel = document.createElement('label');
                    namelabel.innerHTML = defaultProperties[key][0];
                    myform.appendChild(namelabel);

                    if (key == "solv_kind") {

                        //Here we create a "select" element (a drop down list).
                        var newList = document.createElement("select");
                        newList.style.cssText = "float:right";
                        newList.setAttribute("id", key.toString());
                        var dropdownItems = setup("getArray");

                        // Iterate over the dropdown options and create html elements
                        dropdownItems.forEach(function(value, i) {
                            option = document.createElement('option');
                            option.value = i.toFixed(1);
                            option.text = value;
                            newList.appendChild(option);
                        });
                        newList.selectedIndex = defaultProperties[key][2];
                        myform.appendChild(newList);

                    } else {
                        var input = document.createElement("input");
                        input.name = key;
                        input.value = defaultProperties[key][2];
                        input.setAttribute("id", key.toString());
                        input.setAttribute("class", "fieldInput");
                        myform.appendChild(input);
                    }

                    // Line break
                    var linebreak = document.createElement('br');
                    myform.appendChild(linebreak);

                    // Line break
                    var linebreak = document.createElement('br');
                    myform.appendChild(linebreak);
                }
            }

            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);

            // Button - Set Context
            var btn = document.createElement("button");
            btn.innerHTML = 'Set Context';
            btn.style.cssText = 'float: left';
            btn.type = "button";
            btn.name = "submit";
            btn.id = "resetButtonProperties";
            btn.onclick = function() {
                // show Set Context
                /*
                    Maverick
                    Added the parameter here as well.
                */
                showSetContext(graph, diagRoot);
            };
            myform.appendChild(btn);

            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);

            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);

            // Button - Submit
            var btn = document.createElement("button");
            btn.innerHTML = 'Submit';
            btn.type = "button";
            btn.name = "submit";

            // Executes when button 'btn' is clicked
            btn.onclick = function() {
                var propertiesObject = {};

                for (var key in defaultProperties) {
                    if (defaultProperties.hasOwnProperty(key)) {
                        propertiesObject[defaultProperties[key][1]] = document.getElementById(key.toString()).value;

                        /*
                            Maverick
                            Adding the corresponding attributes to the <XcosDiagram> tag.
                        */
                        diagRoot[defaultProperties[key][1]] = document.getElementById(key.toString()).value;
                    }
                }

                setup("set", propertiesObject);
                wind.destroy();
            };

            myform.appendChild(btn);


            // Button - Reset
            var btn = document.createElement("button");
            btn.innerHTML = 'Reset';
            btn.type = "button";
            btn.name = "submit";
            btn.id = "resetButtonProperties";
            btn.onclick = function() {
                // Reset
                for (var key in defaultProperties) {
                    if (defaultProperties.hasOwnProperty(key)) {
                        var element = document.getElementById(key.toString());
                        if (key != "solv_kind") {
                            element.value = defaultProperties[key][2];
                        } else {
                            /*
 								Maverick
 								Code modified to reset the drop down list.
                    		*/
                            element.selectedIndex = 0;
                        }
                    }
                }
            };

            myform.appendChild(btn);
            // Base height without fields : 135 px
            height = 135 + 26 * defaultProperties.length + 15;

            content.appendChild(myform);
            var wind = showModalWindow(graph, 'Set Parameters', content, 450, height);
        };

        function showColorWheel(graph, cell, selectProperty) {
            // Create basic structure for the form
            var content = document.createElement('div');
            content.setAttribute("id", "colorProperties");
            // Add Form
            var myform = document.createElement("form");
            myform.method = "";
            myform.setAttribute("id", "formProperties");
            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);
            // Input Title
            var fieldName = 'Color';
            var namelabel = document.createElement('label');
            namelabel.innerHTML = fieldName;
            myform.appendChild(namelabel);
            // Input
            var input = document.createElement("input");
            input.name = fieldName;
            input.value = 0;
            input.style.cssText = 'float: right;';
            input.setAttribute("id", "color");
            myform.appendChild(input);
            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);
            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);
            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);
            var picker = document.createElement('div');
            picker.setAttribute("id", "picker");
            myform.appendChild(picker);
            // Line break
            var linebreak = document.createElement('br');
            myform.appendChild(linebreak);
            // Button - Submit
            var btn = document.createElement("button");
            btn.innerHTML = 'Submit';
            btn.type = "button";
            btn.name = "submit";
            btn.style.cssText = 'margin-left: 75px';
            // Executes when button 'btn' is clicked
            btn.onclick = function() {
                var input = document.getElementById('color').value;
                var style = graph.getModel().getStyle(cell);
                var styleObject = styleToObject(style);
                if (selectProperty == "edgeStrokeColor") {
                    styleObject['strokeColor'] = input;
                } else if (selectProperty == "bgColor") {
                    graph.container.style.backgroundColor = input;
                } else if (selectProperty == "vertexStrokeColor") {
                    styleObject['strokeColor'] = input;
                } else if (selectProperty == "vertexFillColor") {
                    styleObject['fillColor'] = input;
                } else if (selectProperty == "edgeTextColor") {
                    styleObject['fontColor'] = input;
                }
                style = objectToStyle(styleObject);
                graph.getModel().setStyle(cell, style);
                wind.destroy();
            };
            myform.appendChild(btn);
            content.appendChild(myform);
            var wind = showModalWindow(graph, 'Diagram background...', content, 285, 340);
            // Invokes the farbtastic functionality
            $(document).ready(function() {
                $('#picker').farbtastic('#color');
            });
        };

        function createButtonImage(button, image) {
            if (image != null) {
                var img = document.createElement('img');
                img.setAttribute('src', image);
                img.style.width = '16px';
                img.style.height = '16px';
                img.style.verticalAlign = 'middle';
                img.style.marginRight = '2px';
                button.appendChild(img);
            }
        }

        function addIcons(graph, sidebar) {
            var req = mxUtils.load('palettes/palettes.xml');
            var root = req.getDocumentElement();
            var x = root.getElementsByTagName('node')[0];
            var categories = x.getElementsByTagName('node');
            for (var i = 0, nodeLength = categories.length; i < nodeLength; i++) {
                var categoryName = categories[i].getAttribute('name');
                var title = document.createElement('h3');
                title.setAttribute('class', 'accordion-header ui-accordion-header ui-helper-reset ui-state-default ui-accordion-icons ui-corner-all');
                var span = document.createElement('span');
                span.setAttribute('class', 'ui-accordion-header-icon ui-icon ui-icon-triangle-1-e');
                var titleName = document.createTextNode(categoryName);
                title.appendChild(span);
                title.appendChild(titleName);
                sidebar.appendChild(title);
                var newImages = document.createElement('div');
                newImages.setAttribute('class', 'ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom');
                var blocks = categories[i].getElementsByTagName('block');
                for (var j = 0, blockLength = blocks.length; j < blockLength; j++) {
                    var name = blocks[j].getAttribute('name');
                    var icon = blocks[j].getElementsByTagName('icon')[0];
                    var iconPath = icon.getAttribute('path');
                    addSidebarIcon(graph, newImages, name, iconPath);
                }
                sidebar.appendChild(newImages);
            }
        }

        function getImgHTML(name) {
            return '<img src="' + 'blocks/' + name + '.svg' + '" height="80" width="80">';
        }

        function addToolbarButton(editor, toolbar, action, label, image, isTransparent) {
            var button = document.createElement('button');
            button.style.fontSize = '10';
            createButtonImage(button, image);
            if (isTransparent) {
                button.style.background = 'transparent';
                button.style.color = '#FFFFFF';
                button.style.border = 'none';
            }
            mxEvent.addListener(button, 'click', function(evt) {
                editor.execute(action);
            });
            mxUtils.write(button, label);
            button.setAttribute('id', action);
            toolbar.appendChild(button);
        };

        function showModalWindow(graph, title, content, width, height) {
            var background = document.createElement('div');
            background.style.position = 'absolute';
            background.style.left = '0px';
            background.style.top = '0px';
            background.style.right = '0px';
            background.style.bottom = '0px';
            background.style.background = 'black';
            mxUtils.setOpacity(background, 50);
            document.body.appendChild(background);

            if (mxClient.IS_IE) {
                new mxDivResizer(background);
            }

            var x = Math.max(0, document.body.scrollWidth / 2 - width / 2);
            var y = Math.max(10, (document.body.scrollHeight || document.documentElement.scrollHeight) / 2 - height * 2 / 3);
            var wind = new mxWindow(title, content, x, y, width, height, false, true);
            wind.setClosable(true);

            // Fades the background out after after the window has been closed
            wind.addListener(mxEvent.DESTROY, function(evt) {
                graph.setEnabled(true);
                mxEffects.fadeOut(background, 50, true, 10, 30, true);
            });

            graph.setEnabled(false);
            graph.tooltipHandler.hide();
            wind.setVisible(true);
            return wind;
        };

        var flag = 0;

        function addSidebarIcon(graph, sidebar, name, image) {
            // Function that is executed when the image is dropped on
            // the graph. The cell argument points to the cell under
            // the mousepointer if there is one.
            var funct = function(graph, evt, cell, x, y) {
                var parent = graph.getDefaultParent();
                var model = graph.getModel();
                var v1 = null;
                var doc = mxUtils.createXmlDocument();
                model.beginUpdate();
                try {
                    var label = getImgHTML(name); // Will not exist for all blocks
                    var details_instance = new window[name]();
                    var details = details_instance.define();
                    var enc = new mxCodec(mxUtils.createXmlDocument());
                    var node = enc.encode(details);
                    node.setAttribute('label', label);
                    var temp = enc.encode(parent);
                    node.setAttribute('parent', temp.getAttribute('id'));
                    var i, arr = [];
                    var blockModel = details_instance.x.model;
                    var graphics = details_instance.x.graphics;

                    /* To determine number and type of Port*/
                    var inputPorts = [],
                        outputPorts = [],
                        controlPorts = [],
                        commandPorts = [];
                    if (blockModel.in.height != null) {
                        arr = getData(graphics.in_implicit);
                        if (arr.length != 0) {
                            inputPorts = arr;
                        } else {
                            for (i = 0; i < blockModel.in.height; i++) {
                                inputPorts.push("E");
                            }
                        }
                    }
                    if (blockModel.out.height != null) {
                        arr = getData(graphics.out_implicit);
                        if (arr.length != 0) {
                            outputPorts = arr;
                        } else {
                            for (i = 0; i < blockModel.out.height; i++) {
                                outputPorts.push("E");
                            }
                        }
                    }
                    if (blockModel.evtin.height != null) {
                        for (i = 0; i < blockModel.evtin.height; i++) {
                            controlPorts.push("CONTROL");
                        }
                    }
                    if (blockModel.evtout.height != null) {
                        for (i = 0; i < blockModel.evtout.height; i++) {
                            commandPorts.push("COMMAND");
                        }
                    }
                    v1 = graph.insertVertex(parent, null, node, x, y, 80, 80, name);
                    // @Chhavi: Additional attribute to store the block's instance
                    v1.blockInstance = createInstanceTag(details_instance);
                    createPorts(graph, v1, inputPorts, controlPorts, outputPorts, commandPorts);
                    v1.setConnectable(false);
                } finally {
                    model.endUpdate();
                }
                graph.setSelectionCell(v1);
            }

            var para = document.createElement('p');
            var blockFigure = document.createElement('figure');
            var img = document.createElement('img');
            img.setAttribute('src', image);
            var caption = document.createElement('figcaption');
            var blockName = document.createTextNode(name);
            caption.appendChild(blockName);
            blockFigure.appendChild(img);
            blockFigure.appendChild(caption);
            para.appendChild(blockFigure);
            sidebar.appendChild(para);

            var dragElt = document.createElement('div');
            dragElt.style.border = 'dashed black 1px';
            dragElt.style.width = '80px';
            dragElt.style.height = '80px';

            // Creates the image which is used as the drag icon (preview)
            var ds = mxUtils.makeDraggable(img, graph, funct, dragElt, 0, 0, true, true);
            ds.setGuidesEnabled(true);
        };

        // Create ports
        function createPorts(graph, block, left, top, right, bottom) {
            createInputPorts(graph, block, left, top);
            createOutputPorts(graph, block, right, bottom);
        }

        function createInputPorts(graph, block, leftArray, topArray) {
            var topNumber = topArray.length;
            var leftNumber = leftArray.length;
            if (leftNumber != 0) {
                for (var i = 1; i <= leftNumber; i++) {
                    var x = 0;
                    var y = (i / (leftNumber + 1)).toFixed(4);
                    var portType = leftArray[i - 1];
                    createInputPort(graph, block, x, y, portType, 'left', i);
                }
            }
            if (topNumber != 0) {
                for (var i = 1; i <= topNumber; i++) {
                    var x = (i / (topNumber + 1)).toFixed(4);
                    var y = 0;
                    var portType = topArray[i - 1];
                    createInputPort(graph, block, x, y, portType, 'top', i);
                }
            }
        };

        function createOutputPorts(graph, block, rightArray, bottomArray) {
            var bottomNumber = bottomArray.length;
            var rightNumber = rightArray.length;
            if (rightNumber != 0) {
                for (var i = 1; i <= rightNumber; i++) {
                    var x = 1;
                    var y = (i / (rightNumber + 1)).toFixed(4);
                    var portType = rightArray[i - 1];
                    createOutputPort(graph, block, x, y, portType, 'right', i);
                }
            }
            if (bottomNumber != 0) {
                for (var i = 1; i <= bottomNumber; i++) {
                    var x = (i / (bottomNumber + 1)).toFixed(4);
                    var y = 1;
                    var portType = bottomArray[i - 1];
                    createOutputPort(graph, block, x, y, portType, 'bottom', i);
                }
            }
        };

        function createInputPort(graph, block, x, y, portType, position, ordering) {
            var port = null;
            if (portType == 'COMMAND') {
                port = graph.insertVertex(block, null, 'CommandPort', x, y, 10, 10, 'CommandPort', true);
            } else if (portType == 'CONTROL') {
                port = graph.insertVertex(block, null, 'ControlPort', x, y, 10, 10, 'ControlPort', true);
            } else if (portType == 'I') {
                port = graph.insertVertex(block, null, 'ImplicitInputPort', x, y, 10, 10, 'ImplicitInputPort', true);
            } else if (portType == 'E') {
                port = graph.insertVertex(block, null, 'ExplicitInputPort', x, y, 10, 10, 'ExplicitInputPort', true);
            }
            if (port != null) {
                if (position == 'top') {
                    port.geometry.offset = new mxPoint(-6, -10);
                } else if (position == 'left') {
                    port.geometry.offset = new mxPoint(-10, -6);
                }
                port.ordering = ordering;
            }
        };

        function createOutputPort(graph, block, x, y, portType, position, ordering) {
            var port = null;
            if (portType == 'COMMAND') {
                port = graph.insertVertex(block, null, 'CommandPort', x, y, 10, 10, 'CommandPort', true);
            } else if (portType == 'CONTROL') {
                port = graph.insertVertex(block, null, 'ControlPort', x, y, 10, 10, 'ControlPort', true);
            } else if (portType == 'I') {
                port = graph.insertVertex(block, null, 'ImplicitOutputPort', x, y, 10, 10, 'ImplicitOutputPort', true);
            } else if (portType == 'E') {
                port = graph.insertVertex(block, null, 'ExplicitOutputPort', x, y, 10, 10, 'ExplicitOutputPort', true);
            }
            if (port != null) {
                if (position == 'bottom') {
                    port.geometry.offset = new mxPoint(-6, 0);
                }
                if (position == 'right') {
                    port.geometry.offset = new mxPoint(0, -6);
                }
                port.ordering = ordering;
            }
        };

        function configureStylesheet(graph) {
            var req = mxUtils.load('styles/Xcos-style.xml');
            var root = req.getDocumentElement();
            var dec = new mxCodec(root.ownerDocument);
            dec.decode(root, graph.stylesheet);
        };
    </script>
    <!--
    	Updates connection points before the routing is called.
    -->
    <script type="text/javascript">
        // Computes the position of edge to edge connection points.
        mxGraphView.prototype.updateFixedTerminalPoint = function(edge, terminal, source, constraint) {
            var pt = null;

            if (constraint != null) {
                pt = this.graph.getConnectionPoint(terminal, constraint);
            }

            if (source) {
                edge.sourceSegment = null;
            } else {
                edge.targetSegment = null;
            }

            if (pt == null) {
                var s = this.scale;
                var tr = this.translate;
                var orig = edge.origin;
                var geo = this.graph.getCellGeometry(edge.cell);
                pt = geo.getTerminalPoint(source);

                // Computes edge-to-edge connection point
                if (pt != null) {
                    pt = new mxPoint(s * (tr.x + pt.x + orig.x),
                        s * (tr.y + pt.y + orig.y));

                    // Finds nearest segment on edge and computes intersection
                    if (terminal != null && terminal.absolutePoints != null) {
                        var seg = mxUtils.findNearestSegment(terminal, pt.x, pt.y);

                        // Finds orientation of the segment
                        var p0 = terminal.absolutePoints[seg];
                        var pe = terminal.absolutePoints[seg + 1];
                        var horizontal = (p0.x - pe.x == 0);

                        // Stores the segment in the edge state
                        var key = (source) ? 'sourceConstraint' : 'targetConstraint';
                        var value = (horizontal) ? 'horizontal' : 'vertical';
                        edge.style[key] = value;

                        // Keeps the coordinate within the segment bounds
                        if (horizontal) {
                            pt.x = p0.x;
                            pt.y = Math.min(pt.y, Math.max(p0.y, pe.y));
                            pt.y = Math.max(pt.y, Math.min(p0.y, pe.y));
                        } else {
                            pt.y = p0.y;
                            pt.x = Math.min(pt.x, Math.max(p0.x, pe.x));
                            pt.x = Math.max(pt.x, Math.min(p0.x, pe.x));
                        }
                    }
                }
                // Computes constraint connection points on vertices and ports
                else if (terminal != null && terminal.cell.geometry.relative) {
                    pt = new mxPoint(this.getRoutingCenterX(terminal),
                        this.getRoutingCenterY(terminal));
                }
            }

            edge.setAbsoluteTerminalPoint(pt, source);
        };
    </script>

    <!--
      Overrides methods to preview and create new edges.
    -->
    <script type="text/javascript">
        // Sets source terminal point for edge-to-edge connections.
        mxConnectionHandler.prototype.createEdgeState = function(me) {
            var edge = this.graph.createEdge();

            if (this.sourceConstraint != null && this.previous != null) {
                edge.style = mxConstants.STYLE_EXIT_X + '=' + this.sourceConstraint.point.x + ';' +
                    mxConstants.STYLE_EXIT_Y + '=' + this.sourceConstraint.point.y + ';';
            } else if (this.graph.model.isEdge(me.getCell())) {
                var scale = this.graph.view.scale;
                var tr = this.graph.view.translate;
                var pt = new mxPoint(this.graph.snap(me.getGraphX() / scale) - tr.x,
                    this.graph.snap(me.getGraphY() / scale) - tr.y);
                edge.geometry.setTerminalPoint(pt, true);
            }

            return this.graph.view.createState(edge);
        };

        mxConnectionHandler.prototype.isStopEvent = function(me) {
            return me.getState() != null || mxEvent.isRightMouseButton(me.getEvent());
        };

        // Updates target terminal point for edge-to-edge connections.
        mxConnectionHandlerUpdateCurrentState = mxConnectionHandler.prototype.updateCurrentState;
        mxConnectionHandler.prototype.updateCurrentState = function(me) {
            mxConnectionHandlerUpdateCurrentState.apply(this, arguments);

            if (this.edgeState != null) {
                this.edgeState.cell.geometry.setTerminalPoint(null, false);

                if (this.shape != null && this.currentState != null &&
                    this.currentState.view.graph.model.isEdge(this.currentState.cell)) {
                    var scale = this.graph.view.scale;
                    var tr = this.graph.view.translate;
                    var pt = new mxPoint(this.graph.snap(me.getGraphX() / scale) - tr.x,
                        this.graph.snap(me.getGraphY() / scale) - tr.y);
                    this.edgeState.cell.geometry.setTerminalPoint(pt, false);
                }
            }
        };

        // Updates the terminal and control points in the cloned preview.
        mxEdgeSegmentHandler.prototype.clonePreviewState = function(point, terminal) {
            var clone = mxEdgeHandler.prototype.clonePreviewState.apply(this, arguments);
            clone.cell = clone.cell.clone();

            if (this.isSource || this.isTarget) {
                clone.cell.geometry = clone.cell.geometry.clone();

                // Sets the terminal point of an edge if we're moving one of the endpoints
                if (this.graph.getModel().isEdge(clone.cell)) {
                    clone.cell.geometry.setTerminalPoint(point, this.isSource);
                } else {
                    clone.cell.geometry.setTerminalPoint(null, this.isSource);
                }
            }

            return clone;
        };

        var mxEdgeHandlerConnect = mxEdgeHandler.prototype.connect;
        mxEdgeHandler.prototype.connect = function(edge, terminal, isSource, isClone, me) {
            var result = null;
            var model = this.graph.getModel();
            var parent = model.getParent(edge);

            model.beginUpdate();
            try {
                result = mxEdgeHandlerConnect.apply(this, arguments);
                var geo = model.getGeometry(result);

                if (geo != null) {
                    geo = geo.clone();
                    var pt = null;

                    if (model.isEdge(terminal)) {
                        pt = this.abspoints[(this.isSource) ? 0 : this.abspoints.length - 1];
                        pt.x = pt.x / this.graph.view.scale - this.graph.view.translate.x;
                        pt.y = pt.y / this.graph.view.scale - this.graph.view.translate.y;

                        var pstate = this.graph.getView().getState(
                            this.graph.getModel().getParent(edge));

                        if (pstate != null) {
                            pt.x -= pstate.origin.x;
                            pt.y -= pstate.origin.y;
                        }

                        pt.x -= this.graph.panDx / this.graph.view.scale;
                        pt.y -= this.graph.panDy / this.graph.view.scale;
                    }

                    geo.setTerminalPoint(pt, isSource);
                    model.setGeometry(edge, geo);
                }
            } finally {
                model.endUpdate();
            }

            return result;
        };
    </script>
    <!--
	    Adds in-place highlighting for complete cell area (no hotspot).
    -->
    <script type="text/javascript">
        mxConnectionHandlerCreateMarker = mxConnectionHandler.prototype.createMarker;
        mxConnectionHandler.prototype.createMarker = function() {
            var marker = mxConnectionHandlerCreateMarker.apply(this, arguments);

            // Uses complete area of cell for new connections (no hotspot)
            marker.intersects = function(state, evt) {
                return true;
            };

            return marker;
        };

        mxEdgeHandlerCreateMarker = mxEdgeHandler.prototype.createMarker;
        mxEdgeHandler.prototype.createMarker = function() {
            var marker = mxEdgeHandlerCreateMarker.apply(this, arguments);

            // Adds in-place highlighting when reconnecting existing edges
            marker.highlight.highlight = this.graph.connectionHandler.marker.highlight.highlight;

            return marker;
        }
    </script>
    <!--
	   Implements a perpendicular wires connection edge style
    -->
    <script type="text/javascript">
        mxEdgeStyle.WireConnector = function(state, source, target, hints, result) {
            // Creates array of all way- and terminalpoints
            var pts = state.absolutePoints;
            var horizontal = true;
            var hint = null;

            // Gets the initial connection from the source terminal or edge
            if (source != null && state.view.graph.model.isEdge(source.cell)) {
                horizontal = state.style['sourceConstraint'] == 'horizontal';
            } else if (source != null) {
                horizontal = source.style['portConstraint'] != 'vertical';

                // Checks the direction of the shape and rotates
                var direction = source.style[mxConstants.STYLE_DIRECTION];

                if (direction == 'north' || direction == 'south') {
                    horizontal = !horizontal;
                }
            }

            // Adds the first point
            var pt = pts[0];

            if (pt == null && source != null) {
                pt = new mxPoint(state.view.getRoutingCenterX(source), state.view.getRoutingCenterY(source));
            } else if (pt != null) {
                pt = pt.clone();
            }

            var first = pt;

            // Adds the waypoints
            if (hints != null && hints.length > 0) {
                for (var i = 0; i < hints.length; i++) {
                    horizontal = !horizontal;
                    hint = state.view.transformControlPoint(state, hints[i]);

                    if (horizontal) {
                        if (pt.y != hint.y) {
                            pt.y = hint.y;
                            result.push(pt.clone());
                        }
                    } else if (pt.x != hint.x) {
                        pt.x = hint.x;
                        result.push(pt.clone());
                    }
                }
            } else {
                hint = pt;
            }

            // Adds the last point
            pt = pts[pts.length - 1];

            if (pt == null && target != null) {
                pt = new mxPoint(state.view.getRoutingCenterX(target), state.view.getRoutingCenterY(target));
            }

            if (horizontal) {
                if (pt.y != hint.y && first.x != pt.x) {
                    result.push(new mxPoint(pt.x, hint.y));
                }
            } else if (pt.x != hint.x && first.y != pt.y) {
                result.push(new mxPoint(hint.x, pt.y));
            }
        };

        mxStyleRegistry.putValue('wireEdgeStyle', mxEdgeStyle.WireConnector);

        // This connector needs an mxEdgeSegmentHandler
        mxGraphCreateHandler = mxGraph.prototype.createHandler;
        mxGraph.prototype.createHandler = function(state) {
            var result = null;

            if (state != null) {
                if (this.model.isEdge(state.cell)) {
                    var style = this.view.getEdgeStyle(state);

                    if (style == mxEdgeStyle.WireConnector) {
                        return new mxEdgeSegmentHandler(state);
                    }
                }
            }

            return mxGraphCreateHandler.apply(this, arguments);
        };
    </script>

</head>

<!-- Page passes the container for the graph to the program -->

<body onload="main(document.getElementById('graphContainer'),
			document.getElementById('outlineContainer'),
		 	document.getElementById('toolbarContainer'),
			document.getElementById('sidebarContainer'),
			document.getElementById('statusContainer'));" style="margin:0px;">

    <!-- Creates a container for the splash screen -->
    <div id="splash" style="position:absolute;top:0px;left:0px;width:100%;height:100%;background:white;z-index:1;">
        <center id="splash" style="padding-top:230px;">
            <img src="images/loading.gif">
        </center>
    </div>

    <!-- Creates a container for the sidebar -->
    <div id="toolbarContainer" style="position:absolute;white-space:nowrap;overflow:hidden;top:0px;left:0px;max-height:24px;height:36px;right:0px;padding:6px;background-image:url('images/toolbar_bg.gif');">
    </div>

    <!-- Creates a container for the toolbox -->
    <div id="sidebarContainer" class="ui-accordion ui-widget ui-helper-reset" style="position:absolute;overflow:scroll;top:36px;left:0px;bottom:36px;max-width:266px;width:270px;padding-top:10px;padding-left:4px;">
    </div>

    <!-- Creates a container for the graph -->
    <div id="graphContainer" style="position:absolute;overflow:hidden;top:36px;left:270px;bottom:36px;right:0px;background-image:url('images/grid.gif');cursor:default;">
    </div>

    <!-- Creates a container for the outline -->
    <div id="outlineContainer" style="position:absolute;overflow:hidden;top:36px;right:0px;width:200px;height:140px;background:transparent;border-style:solid;border-color:black;">
    </div>

    <!-- Creates a container for the sidebar -->
    <div id="statusContainer" style="text-align:right;position:absolute;overflow:hidden;bottom:0px;left:0px;max-height:24px;height:36px;right:0px;color:white;padding:6px;background-image:url('images/toolbar_bg.gif');">
        <div style="font-size:10pt;float:left;">
            <a href="http://fossee.in/" target="_tab">FOSSEE</a>
        </div>
    </div>

    <!-- Secret -->
    <p class="accordion-expand-holder" style="display:none">
        <a id='toggleBlocks' class="accordion-expand-all">Expand All</a>
    </p>

</body>
<!-- It's good if this part happens after the entire page has loaded-->
<script type="text/javascript">
    // Preload all images
    var dir = ["blocks", "images"];
    var fileextension = ".";
    var blockImages = [];
    $.each(dir, function(index, value) {
        $.ajax({ // http://stackoverflow.com/a/18480589
            url: value,
            success: function(data) {
                $(data).find("a:contains(" + fileextension + ")").each(function() {
                    var filename = this.href.replace(window.location.host, "");
                    filename = filename.replace("https://", value);
                    filename = filename.replace("http://", value);
                    blockImages.push(filename);
                });
                // Prevent multi-threading and have function within call!
                function preload(sources) {
                    var images = [];
                    for (var i = 0, length = sources.length; i < length; ++i) {
                        images[i] = new Image();
                        images[i].src = sources[i];
                    }
                }
                preload(blockImages);
            }
        });
    });
    //Find out more here: http://stackoverflow.com/questions/12843418/jquery-ui-accordion-expand-collapse-all
    $(window).load(function() {
        var headers = $('#sidebarContainer .accordion-header');
        var contentAreas = $('#sidebarContainer .ui-accordion-content ').hide();
        var expandLink = $('.accordion-expand-all');

        // add the accordion functionality
        headers.click(function() {
            var panel = $(this).next();
            var isOpen = panel.is(':visible');

            // open or close as necessary
            panel[isOpen ? 'slideUp' : 'slideDown']()
                // trigger the correct custom event
                .trigger(isOpen ? 'hide' : 'show');

            // stop the link from causing a pagescroll
            return false;
        });

        // hook up the expand/collapse all
        expandLink.click(function() {
            var isAllOpen = $(this).data('isAllOpen');

            contentAreas[isAllOpen ? 'hide' : 'show']()
                .trigger(isAllOpen ? 'hide' : 'show');
        });

        // when panels open or close, check to see if they're all open
        contentAreas.on({
            // whenever we open a panel, check to see if they're all open
            // if all open, swap the button to collapser
            show: function() {
                var isAllOpen = !contentAreas.is(':hidden');
                if (isAllOpen) {
                    expandLink.text('Collapse All')
                        .data('isAllOpen', true);
                }
            },
            // whenever we close a panel, check to see if they're all open
            // if not all open, swap the button to expander
            hide: function() {
                var isAllOpen = !contentAreas.is(':hidden');
                if (!isAllOpen) {
                    expandLink.text('Expand All')
                        .data('isAllOpen', false);
                }
            }
        });
    });
</script>

</html>