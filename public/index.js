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

var Bossanovajs = require('../vendor/bossanovajs/render.js');

var Bossanova = new Bossanovajs();

Bossanova.get().listen(3001);
