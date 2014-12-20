<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 支持虾米音乐的播放器
 *
 * @package Minty
 * @author shingchi
 * @version 1.0.1
 * @link https://github.com/shingchi
 */
class Minty_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        if (!extension_loaded('memcache')) {
            throw new Typecho_Plugin_Exception(_t('对不起, 您的主机不支持 Memcache 服务, 无法使用此插件'));
        }

        // 编辑按钮
        Typecho_Plugin::factory('admin/editor-js.php')->markdownEditor = array('Minty_Plugin', 'addButton');

        // 前端输出
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('Minty_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Minty_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Archive')->header = array('Minty_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('Minty_Plugin', 'footer');

        Helper::addAction('minty', 'Minty_Action');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeAction('minty');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){}

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 短代码解析
     *
     * @access public
     * @return void
     */
    public static function parse($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;

        if ($widget instanceof Widget_Archive) {
            $pattern = '/<p>\[Minty auto=(.*)(\s)loop=(.*)(\s)type=(.*)(\s)songs=(.*)\]<\/p>/i';
            $replace = '<div class="minty" data-auto="' . '\1' . '" data-loop="' . '\3' . '" data-type="' . '\5' . '" data-songs="' . '\7' . '">
    <div class="minty-controls">
        <div class="minty-detail">歌曲 - 艺术家</div>
        <div class="minty-progress">
            <div class="minty-progress-loaded"></div>
            <div class="minty-progress-played"></div>
        </div>
        <div class="minty-duration">00:00</div>
        <i class="minty-button-play"></i>
        <i class="minty-button-volume"></i>
        <i class="minty-button-menu"></i>
    </div>
    <ul class="minty-playlist"></ul>
</div>';

            $content = preg_replace($pattern, $replace, $content);
        }

        return $content;
    }

    /**
     * 顶部输出
     *
     * @access public
     * @return void
     */
    public static function header()
    {
        $css = Helper::options()->pluginUrl . '/Minty/dist/css/minty.min.css';
        echo '<link rel="stylesheet" href="' . $css . '">' . "\n";
    }

    /**
     * 底部输出
     *
     * @access public
     * @return void
     */
    public static function footer()
    {
        Typecho_Widget::widget('Widget_Options')->to($options);
        $jq = $options->pluginUrl . '/Minty/dist/js/jquery.min.js';
        $sm = $options->pluginUrl . '/Minty/dist/js/soundmanager2.min.js';
        $mt = $options->pluginUrl . '/Minty/dist/js/minty.min.js';
        $swf = $options->pluginUrl . '/Minty/dist/swf';
?><script>
  // Minty Config
  var minty = {
    apiUrl: '<?php $options->index('/action/minty'); ?>',
    swfUrl: '<?php echo $swf; ?>/'
  };
</script>
<?php
        echo '<script src="' . $jq . '"></script>' . "\n";
        echo '<script src="' . $sm . '"></script>' . "\n";
        echo '<script src="' . $mt . '"></script>' . "\n";
    }

    /**
     * 创建编辑器按钮
     *
     * @access public
     * @return void
     */
    public static function addButton()
    {
        ?>// 播放器按钮
    editor.hooks.chain('makeButton', function(buttons, makeButton, bindCommand, ui) {
        buttons.minty = makeButton('wmd-minty-button', '音乐 [Minty] Ctrl+X', '0', function(chunk, postProcessing) {
            var background = ui.createBackground();

            ui.prompt("<p><b>插入音乐</b></p><p>1. 自动(auto) 和循环(loop): <b>1 或 0</b></p><p>2. 类型: 单曲(song),列表(list),专辑(album),精选集(collect)</p><p>3. 输入框可以输入虾米单曲、专辑、精选集或列表的ID如:<br><b>单曲: 1773431302; 列表: 1769023557,2091668</b></p>", '', function(music) {

                background.parentNode.removeChild(background);

                if (music !== null) {
                    music = music.replace("http://", "");
                    chunk.startTag = "[Minty auto=自动 loop=循环 type=类型 songs=" + music + "]";
                    chunk.endTag = "";
                }
                postProcessing();

            }, '确定', '取消');
        });

        // 按钮样式
        var button = buttons.minty.getElementsByTagName("span")[0];
        button.style.backgroundImage = "none";

        buttons.minty.style.backgroundImage = "url(<?php echo Typecho_Common::url('dist/image/music.png', Helper::options()->pluginUrl('Minty')); ?>)";
        buttons.minty.style.backgroundRepeat = "no-repeat";
        buttons.minty.style.backgroundPosition = "3px 3px";

        // 快捷键
        document.getElementById("text").addEventListener("keydown", function(key) {
            if ((key.ctrlKey || key.metaKey) && !key.altKey && !key.shiftKey) {
                var keyCode = key.charCode || key.keyCode;
                var keyCodeStr = String.fromCharCode(keyCode).toLowerCase();

                if (keyCodeStr == "x") {
                    buttons.minty.click();
                }
            }
        }, false);
    });
        <?php
    }
}
