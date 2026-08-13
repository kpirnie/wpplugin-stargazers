const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: path.resolve(process.cwd(), 'source/work/blocks-src/index.js'),
    output: {
        path: path.resolve(process.cwd(), 'source/build'),
        filename: 'blocks.js'
    }
};