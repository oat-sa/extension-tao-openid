module.exports = function(grunt) {
    'use strict';

    var sass    = grunt.config('sass') || {};
    var watch   = grunt.config('watch') || {};
    var notify  = grunt.config('notify') || {};
    var root    = grunt.option('root') + '/taoOpenId/views/';

    sass.taoopenid = { };
    sass.taoopenid.files = { };
    sass.taoopenid.files[root + 'css/openid.css'] = root + 'scss/openid.scss';

    watch.taoopenidsass = {
        files : [root + 'scss/**/*.scss'],
        tasks : ['sass:taoopenid', 'notify:taoopenidsass'],
        options : {
            debounceDelay : 1000
        }
    };

    notify.taoopenidsass = {
        options: {
            title: 'Grunt SASS',
            message: 'SASS files compiled to CSS'
        }
    };

    grunt.config('sass', sass);
    grunt.config('watch', watch);
    grunt.config('notify', notify);

    //register an alias for main build
    grunt.registerTask('taoopenidsass', ['sass:taoopenid']);
};
