/**
 * (c) 2017 Bossanova NodeJs Framework 1.0.1
 * http://bossanova.uk/nodejs
 *
 * @category PHP
 * @package  BossanovaJS
 * @author   Paul Hodel <paul.hodel@gmail.com>
 * @license  The MIT License (MIT)
 * @link     http://bossanova.uk/js
 */

'use strict';

var http = require('http');
var fs   = require('fs');

class Bossanova {

    constructor() {
        // Default render configuration
        this.configuration = {
            'template' : {
                'area' : 'content',
                'path' : 'templates/default/index.html',
                'render' : true,
                'recursive' : true,
                'meta' : {
                    'title' : '',
                    'author' : '',
                    'description' : '',
                    'keywords' : '',
                },
                'path404' : false,
            },
            'module' : {
                'name' : '',
                'controller' : '',
                'view' : false,
            },
            'requests' : false
        };
    }

    header(num)
    {
        this.res.writeHead(num);
    }

    get() {
        // Self instance
        var bossanova = this;

        // Http server
        return this.server = http.createServer(function(req, res) {
            // Keep important information about the request
            bossanova.requestedFile = '.' + req.url.toString().split('?')[0];
            bossanova.urlParam = req.url.substring(1).split('/');
            bossanova.req = req;
            bossanova.res = res;

            // Existing file
            if (bossanova.urlParam[0] && fs.existsSync(bossanova.requestedFile)) {
                if (fs.lstatSync(bossanova.requestedFile).isFile()) {
                    // Return the file content
                    res.end(fs.readFileSync(bossanova.requestedFile));
                } else {
                    // Denied
                    bossanova.header(403);
                    res.end('<h1>Forbidden</h1>');
                }
            } else {
                // Run content
                res.end(bossanova.run());
            }
        });
    }
    

    run()
    {
        var html = '';
        var content = [];

        // Possible module filename
        var moduleFilename = '../modules/' + this.urlParam[0] + '/' + this.urlParam[0] + '.js';

        // Check module filename
        if (this.urlParam[0]) {
            try {
                // Module exists
                if (fs.existsSync(moduleFilename)) {
                    // Not sure why path is different for require, redefine
                    var moduleFilename = '../../modules/' + this.urlParam[0] + '/' + this.urlParam[0] + '.js';

                    // Load module
                    var module = require(moduleFilename);

                    // Verify if the second URL param is a module controller
                    if (this.urlParam[1]) {
                        // View to be checked
                        var viewFilename = '../modules/' + this.urlParam[0] + '/views/' + this.urlParam[1] + '.html';

                        // Verify method to be called
                        try {
                            var controllerFilename = '../../modules/' + this.urlParam[0] + '/controllers/' + this.urlParam[1] + '.js';
                            var controller = require(controllerFilename);

                            // Create controller instance
                            var requestInstance = new controller();

                            // The third argument is a method inside de controller
                            if (this.urlParam[2] && requestInstance[this.urlParam[2]]) {
                                var methodName = this.urlParam[2];
                            } else {
                                var methodName = 'index';
                            }
                        } catch (err) {
                        }
                    } else {
                        // View to be checked
                        var viewFilename = '../modules/' + this.urlParam[0] + '/views/' + this.urlParam[0] + '.html';
                    }

                    // Request based in the module
                    if (! methodName) {
                        // Create the module instance
                        var requestInstance = new module();

                        // The second argument is not an controller check if it is a method
                        if (this.urlParam[1] && requestInstance[this.urlParam[1]]) {
                            var methodName = this.urlParam[1];
                        } else {
                            var methodName = 'index';
                        }
                    }

                    // Create custom module features
                    this.appendCommonMethods(requestInstance);

                    // Call requested method
                    content[this.configuration.template.area] = requestInstance[methodName]();

                    // Check for existing view @TODO: cache view content to avoid IO in each request.
                    if (viewFilename) {
                        if (fs.existsSync(viewFilename)) {
                            content[this.configuration.template.area] += fs.readFileSync(viewFilename).toString();
                        }
                    }

                    // Call subcontents

                } else {
                    // Module do not exists
                    this.header(404);
                    this.configuration.template.path = this.configuration.template.path404;

                    // Content
                    content[this.configuration.template.area] = '<h1>Not found</h1>';
                }

            } catch (err) {
                console.log(err);
            }
        }

        // Render layout
        html = this.renderTemplate(content);

        return html;
    }

    /**
     * Append default methods on the user class
     * 
     */
    appendCommonMethods(moduleInstance)
    {
        // Create database instance
        var database = require('./database.js');

        // Get param
        var params = this.urlParam;

        moduleInstance['getParam'] = function(index) {
            if (! index) {
                index = 0;
            }

            return params[index] ? params[index] : null; 
        };

        // Get post
    }
    
    /**
     * Render the template layout
     */
    renderTemplate(content)
    {
        var html = '';

        // Load layout
        try {
            if (this.configuration.template.render == true && this.configuration.template.path) {
                // Layout exists?
                if (fs.existsSync('../public/' + this.configuration.template.path)) {
                    // Load HTML content from the template
                    var layout = fs.readFileSync('../public/' + this.configuration.template.path).toString();

                    // Append right content to the html ids
                    for (var i = 0, len = layout.length; i < len; i++) {
                        html += layout[i];
                    }
                    
                    // Replace meta
                }
            }
        } catch (err) {
        }

        if (! html) {
            html = content[this.configuration.template.area];
        } else {
            // Replace contents
            var insideTag = 0;
            var insideProperty = 0;
            var found = null;
            var id = '';

            // Layout reference
            var test = html.toLowerCase();

            // First character
            var merged = html[0];

            // Scan full layout
            for (i = 1; i < html.length; i++) {
                merged += html[i];

                // Inside a tag
                if (insideTag > 0) {
                    // Inside an id property?
                    if (insideTag > 1) {
                        if (insideTag == 2) {
                            // Found [=]
                            if (test[i] == String.fromCharCode(61)) {
                                insideTag = 3;
                            } else {
                                // [space], ["], [']
                                if (test[i] != String.fromCharCode(32) &&
                                    test[i] != String.fromCharCode(34) &&
                                    test[i] != String.fromCharCode(39)) {
                                    insideTag = 1;
                                }
                            }
                        } else {
                            // Separate any valid id character
                            if ((test[i].charCodeAt(0) >= 0x30 && test[i].charCodeAt(0) <= 0x39) ||
                                (test[i].charCodeAt(0) >= 0x61 && test[i].charCodeAt(0) <= 0x7A) ||
                                (test[i].charCodeAt(0) == 95) ||
                                (test[i].charCodeAt(0) == 45)) {
                                id += test[i];
                            }

                            // Checking end of the id string
                            if (id) {
                                // Check for an string to be closed in the next character [>], [space], ["], [']
                                if (test[i + 1] == String.fromCharCode(62) ||
                                    test[i + 1] == String.fromCharCode(32) ||
                                    test[i + 1] == String.fromCharCode(34) ||
                                    test[i + 1] == String.fromCharCode(39)) {
                                    // Id found mark flag
                                    if (typeof(content[id]) == 'string') {
                                        found = content[id];
                                    }

                                    id = '';
                                    insideTag = 1;
                                }
                            }
                        }
                    } else if (test[i - 1] == String.fromCharCode(105) && test[i] == String.fromCharCode(100)) {
                        // id found start testing
                        insideTag = 2;
                    }
                }

                // Tag found <
                if (test[i - 1] == String.fromCharCode(60)) {
                    insideTag = 1;
                }

                // End of a tag >
                if (test[i] == String.fromCharCode(62)) {
                    id = '';
                    insideTag = 0;

                    // Inserted content in the correct position
                    if (found) {
                        merged += found;
                        found = '';
                    }
                }
            }

            // Meta Base
            var base = this.configuration.template.path.split('/');

            // Remove filename
            base.pop();

            // Base address
            base = '//' + this.req.rawHeaders[1] + '/' + base.join('/') + '/'; 

            // Adding base href
            merged = merged.replace("<head>", "<head>\r\n<base href='" + base + "'>");

            // Merged template
            html = merged;
        }

        return html;
    }
}

module.exports = Bossanova;