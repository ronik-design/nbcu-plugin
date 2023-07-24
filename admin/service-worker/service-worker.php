<?php 
    $f_custom_js_settings = get_field('custom_js_settings', 'options');
    $f_custom_scripts_loader = get_field('custom_js_settings_serviceworker_script_loader', 'options');

    $f_cache = apply_filters( 'ronik_cache_busting_proto', false );
    $f_custom_scripts = '';
    if($f_custom_scripts_loader){
        $f_custom_scripts_count = count($f_custom_scripts_loader);
        foreach($f_custom_scripts_loader as $key => $script){
            if( ($key+1) == $f_custom_scripts_count ){
                $f_custom_scripts .= "'".$script['script']."'";
            } else {
                $f_custom_scripts .= "'".$script['script'].$f_cache ."'" . ',';
            }
        }
    }

    if(isset($f_custom_js_settings['enable_serviceworker']) && $f_custom_js_settings['enable_serviceworker']){
        ob_start();
    ?>
                if (location.pathname.indexOf('/wp-admin/') !== -1) {
                    console.log('Skipping service worker registration in wp-admin');
                } else {
                    var isUserLoggedIn = false;
                    function setUserStatus() {
                    return fetch('/wp-json/wp/v2/users/me', { credentials: 'include' })
                    .then(function (res) {
                        return res.json().then(function (json) {
                            if (json.hasOwnProperty('id')) {
                            isUserLoggedIn = true;
                            }
                        });
                    });
                    }

                    function removeDuplicates(arr) {
                    return arr.filter((item, index) => arr.indexOf(item) === index);
                    }
                
                    // register your service worker code here
                    console.log('Service Worker Initialized');
                    var cacheName;
                    // Fetch the images from the WordPress REST API and cache them
                    self.addEventListener('install', function(event) {
                    event.waitUntil(setUserStatus());
                    console.log('Service Worker Installed');
                    event.waitUntil(
                        // Lets get the wp version for cahce busting purposes...
                        fetch('/wp-json/serviceworker/v1/data/version')
                        .then(function(response) {
                            return response.json();
                        })
                        .then(function(json) {
                            var $version;
                            $version = json;
                            cacheName = 'my-cached-'+$version[0];
                            // Lets get all the images from the WordPress REST API
                            fetch('/wp-json/serviceworker/v1/data/image')
                            .then(function(response) {
                            return response.json();
                            })
                            .then(function(json) {
                            return caches.open(cacheName)
                                .then(function(cache) {
                                // Add all images to the cache
                                var imageUrls = json.map(function(item) {
                                    console.log('Service Worker Caching Files');
                                    return item;
                                });
                                var cacheFiles = [
                                    '/wp-includes/js/jquery/jquery.min.js',
                                    '/wp-includes/css/classic-themes.min.css',
                                    // Plugin dependencies
                                    '/wp-content/plugins/ronikdesign/public/css/ronikdesign-public.css'+`?ver=${$version[1]}`,
                                    '/wp-content/plugins/ronikdesign/public/assets/dist/main.min.css'+`?ver=${$version[1]}`,
                                    <?php echo $f_custom_scripts; ?>
                                ]
                                const children = cacheFiles.concat(imageUrls);

                                    fetch('/wp-json/serviceworker/v1/data/url')
                                    .then(function(response) {
                                    return response.json();
                                    })
                                    .then(function(json) {
                                    const scriptstyles = json;
                                    newChild = removeDuplicates(children.concat(scriptstyles));
                                    console.log(newChild);
                                    return newChild;
                                    })

                                    fetch('/wp-json/serviceworker/v1/data/sitemap')
                                    .then(function(response) {
                                    return response.json();
                                    })
                                    .then(function(json) {
                                    const sitemap = json;
                                    newerChild = removeDuplicates(newChild.concat(sitemap));
                                    return cache.addAll(newerChild);
                                    })
                                    .catch(function(error) {
                                    console.log('Error: ' + error);
                                    })
                                });
                            })
                            .catch(function(error) {
                            console.log('Error: ' + error);
                            })
                        })
                        .catch(function(error) {
                            console.log('Error: ' + error);
                        })
                    );
                    });
                
                    // Serve image requests from cache if available, or fetch them
                    self.addEventListener('fetch', function(event) {
                    if (event.request.method === 'GET') {
                        event.respondWith(
                        caches.open(cacheName)
                            .then(function(cache) {
                            if(!isUserLoggedIn){
                                console.log('user not logged in');
                                return cache.match(event.request)
                                .then(function(response) {
                                    return response || fetch(event.request);
                                });
                            }
                            })
                        );
                    }
                    });
                }

        <?php
        $output = ob_get_contents();
        ob_end_clean();

        // We dynamically create the service worker file.
        $random_file = fopen(explode("wp-content/", get_stylesheet_directory())[0]."sw.js", "w");
        fwrite($random_file, $output);
        fclose($random_file);
    }
?>