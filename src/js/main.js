(function($, global, undefined) {
  var Player;
  var players = [];
  var playerSelector = '.minty';
  var apiUrl = minty.apiUrl;
  var swfUrl = minty.swfUrl;

  // 播放器启动
  soundManager.setup({
    html5PollingInterval: 50,
    flashVersion: 9,
    // url: swfUrl
  });

  // 播放器就绪
  soundManager.onready(function() {
    $(playerSelector).each(function() {
      players.push(new Player($(this)));
    });
  });

  // 播放器类
  Player = function(dom, options) {

    // 节点对象
    var instances;

    // 播放器对象
    var controller;

    // 声音对象
    var soundObject;

    // 动作
    var events;

    // 默认播放序号
    var defaultItem;

    // 配置
    var settings;
    var config;

    settings = {
      selecter: {
        controls: '.minty-controls',
        detail: '.minty-detail',
        playlist: '.minty-playlist',
        progress: '.minty-progress',
        progressLoaded: '.minty-progress-loaded',
        progressPlayed: '.minty-progress-played',
        duration: '.minty-duration',
        button: {
          play: '.minty-button-play',
          volume: '.minty-button-volume',
          menu: '.minty-button-menu'
        }
      },
      css: {
        disabled: 'disabled',
        selected: 'selected',
        active: 'active',
        mute: 'mute',
        menu: 'open'
      },
      data: {
        type: null,
        id: null,
        loop: true,
        auto: true,
        selectedIndex: 0,
        sources: null
      }
    };

    // 初始化配置
    function setConfig() {
      config = $.extend({}, settings, options);
    }

    // 初始化节点对象
    function setInstances() {
      var controls;
      var playlist;
      var progress;

      controls = dom.find(config.selecter.controls);
      playlist = dom.find(config.selecter.playlist);
      progress = controls.find(config.selecter.progress);

      instances = {
        player: dom,
        controls: controls,
        playlist: playlist,
        progress: progress,
        progressLoaded: progress.find(config.selecter.progressLoaded),
        progressPlayed: progress.find(config.selecter.progressPlayed),
        duration: controls.find(config.selecter.duration),
        detail: controls.find(config.selecter.detail),
        button: {
          play: controls.find(config.selecter.button.play),
          volume: controls.find(config.selecter.button.volume),
          menu: controls.find(config.selecter.button.menu)
        }
      };
    }

    // 播放控制器
    function Controller() {
      // 播放数据，即API返回数据
      // var data = config.data;

      // 获取列表数据
      function getSources() {
        return config.data.sources;
      }

      // 获取单曲数据
      function getSource(index) {
        var sources;

        if (config.data.selectedIndex === null) {
          return index;
        }

        sources = getSources();
        index = (index !== undefined ? index : config.data.selectedIndex);
        source = sources[index];

        return source;
      }

      // 下一首
      function getNext() {
        var source;
        var total = config.data.sources.length;

        // 让选择位置递增一位
        if (config.data.selectedIndex !== null) {
          config.data.selectedIndex++;
        }

        if (total > 1) {  // 列表多首歌
          if (config.data.selectedIndex >= total) {  // 超过列表
            if (config.data.loop) {
              // 返回第一首歌的数据，并设置选择位置为 0
              source = getSource(0);
              config.data.selectedIndex = 0;
            } else {
              // 返回空数据，并设置选择位置为刚播完的歌曲
              source = getSource(config.data.selectedIndex);
              config.data.selectedIndex--;
            }
          } else {  // 未超过列表
            // 返回下一首歌的数据，选择位置为下一首歌
            source = getSource(config.data.selectedIndex);
          }
        } else {  // 列表一首歌
          if (config.data.loop) {
            // 返回第一首歌数据，并设置选择位置为 0
            source = getSource(0);
            config.data.selectedIndex = 0;
          } else {
            // 返回空数据，并设置位置为 0
            source = getSource(config.data.selectedIndex);
            config.data.selectedIndex = 0;
          }
        }

        return source;
      }

      // 上一首
      function getPrevious() {
        config.data.selectedIndex--;

        if (config.data.selectedIndex < 0) {
          // wrapping around beginning of list? loop or exit.
          if (config.data.loop) {
            config.data.selectedIndex = config.data.sources.length - 1;
          } else {
            // undo
            config.data.selectedIndex++;
          }
        }

        return getSource();
      }

      // 清除选择样式
      function resetSelected() {
        var items;
        var selectedClass = '.' + config.css.selected;

        if (config.data.sources !== null) {
          items = instances.playitem.filter(selectedClass);
        }

        if (items) {
          items.each(function() {
            $(this).removeClass(config.css.selected);
          });
        }
      }

      // 选择单曲
      function select(index) {
        var item;

        // 重置选择样式
        resetSelected();

        if (index !== undefined || index !== null) {
          item = instances.playitem[index];
          $(item).addClass(config.css.selected);
        }

        config.data.selectedIndex = index;
      }

      // 获取地址
      function getURL() {
        var source;
        var url;

        source = getSource();

        if (source) {
          url = source.src;
        }

        return url;
      }

      return {
        getNext: getNext,
        getPrevious: getPrevious,
        getSource: getSource,
        getURL: getURL,
        select: select
      };
    } // End Controller

    // 播放时间
    // ms 为毫秒
    function getTime(ms) {
      var seconds = Math.floor(ms / 1000);
      var m = Math.floor(seconds / 60);
      var s = Math.floor(seconds % 60);

      return ((m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s);
    }

    // 播放文字
    function setTitle(index) {
      var sources;

      sources = config.data.sources;

      instances.detail.html(sources[index].title + ' - ' + sources[index].author);
    }

    // 设置标题
    function setPlaylist() {
      var number = config.data.sources.length;

      if (number > 1) {
        instances.playlist.addClass(config.css.menu);
      }
    }

    // 播放媒体
    function makeSound(url) {
      var sound = soundManager.createSound({
        url: url,

        whileplaying: function() {
          var progressMaxLeft = 100;
          // var left;
          var width;

          // 进度按钮偏移
          // left = Math.min(progressMaxLeft, Math.max(0, (progressMaxLeft * (this.position / this.durationEstimate)))) + '%';

          // 播放进度
          width = Math.min(100, Math.max(0, (100 * this.position / this.durationEstimate))) + '%';

          if (this.duration) {
            // dom.progress.style.left = left;
            // 播放进度
            instances.progressPlayed.css('width', width);
            // 播放时间
            instances.duration.html('-' + getTime(this.duration - this.position));
          }
        },

        // 缓冲状态
        onbufferchange: function(isBuffering) {
          if (isBuffering) {
            instances.player.addClass('buffering');
          } else {
            instances.player.removeClass('buffering');
          }
        },

        // 播放中
        onplay: function() {
          instances.button.play.removeClass('paused').addClass('playing');
        },

        // 暂停
        onpause: function() {
          instances.button.play.removeClass('playing').addClass('paused');
        },

        // 重新播放
        onresume: function() {
          instances.button.play.removeClass('paused').addClass('playing');
        },

        // 加载中
        whileloading: function() {
          var width = ((this.bytesLoaded / this.bytesTotal) * 100) + '%';

          // 加载进度条
          instances.progressLoaded.css('width', width);

          if (!this.isHTML5) {
            instances.duration.html(getTime(this.durationEstimate));
          }
        },

        onload: function(ok) {
          if (ok) {
            instances.duration.html(getTime(this.duration));
          } else if (this._iO && this._iO.onerror) {
            this._iO.onerror();
          }
        },

        onerror: function() {
          var source;

          source = controller.getSource();

          if (source) {
            // instances.detail.html('ERROR - ' + config.data.sources[config.data.selectedIndex].title);
            instances.detail.html('错误: 当前歌曲无法播放');
          }

          // 设置播放按钮为暂停中状态
          instances.button.play.removeClass('playing').addClass('paused');
        },

        onstop: function() {
          instances.button.play.removeClass('playing');
        },

        onfinish: function() {
          var lastIndex;
          var source;

          instances.button.play.removeClass('playing');
          instances.progressPlayed.css('width', 0);
          instances.duration.html('--:--');

          // 获取下一首数据
          source = controller.getNext();

          // 设置播放器信息
          controller.select(config.data.selectedIndex);
          setTitle(config.data.selectedIndex);

          if (source) {
            this.play({
              url: source.src
            });
          }
        }
      });

      return sound;
    }

    function playIndex(index) {
      var source = config.data.sources[index];

      if (soundManager.canPlayURL(source.src)) {
        if (!soundObject) {
          soundObject = makeSound(source.src);
        }

        soundObject.stop();

        controller.select(index);

        setTitle(index);

        soundObject.play({
          url: source.src,
          position: 0
        });
      }
    }

    function getMedia() {
      config.data.type = instances.player.data('type');
      config.data.id = instances.player.data('songs');

      // 设置播放模式
      if (instances.player.data('auto') !== undefined) {
        config.data.auto = instances.player.data('auto');
      }
      if (instances.player.data('loop') !== undefined) {
        config.data.loop = instances.player.data('loop');
      }

      $.ajax({
        url: apiUrl,
        dataType: 'json',
        async: false,
        data: {'do': config.data.type, 'id': config.data.id},
        success: function(result) {
          config.data.sources = result;
        },
        error: function() {
          console.error('无法获取媒体信息');
        }
      });

      // 初始播放列表
      if (config.data.sources && config.data.sources.length > 0) {
        for (var i = 0; i < config.data.sources.length; i++) {
          var title = config.data.sources[i].title;
          var author = config.data.sources[i].author;
          var item = '<li data-index="' + i + '">' + title + ' - ' + author + '</li>';

          instances.playlist.append(item);
        }

        // 初始化播放项目对象
        instances.playitem = instances.playlist.children();
      }
    }

    function init() {
      if (!dom) {
        console.warn('init(): No playerNode element?');
      }

      // 初始化配置
      setConfig();

      // 初始化节点对象
      setInstances();

      // 获取媒体资源并初始化播放列表
      getMedia();

      // 实例化播放控制器
      controller = new Controller();

      // 默认选择第一个
      controller.select(0);

      // 自动播放
      if (config.data.auto) {
        playIndex(0);
      }

      // 设置播放文字
      setTitle(0);

      // 设置菜单
      setPlaylist();

      // 绑定点击监听
      events.add(instances.player.get(0), 'click', handleClick);

      // 绑定滚动条监听
      events.add(instances.progress.get(0), 'click', handleShuffle);
    }

    // 事件绑定
    events = {
      add: function(o, evtName, evtHandler) {
        // return an object with a convenient detach method.
        var eventObject = {
          detach: function() {
            return remove(o, evtName, evtHandler);
          }
        };

        if (window.addEventListener) {
          o.addEventListener(evtName, evtHandler, false);
        } else {
          o.attachEvent('on' + evtName, evtHandler);
        }

        return eventObject;
      },

      remove: (window.removeEventListener !== undefined ? function(o, evtName, evtHandler) {
        return o.removeEventListener(evtName, evtHandler, false);
      } : function(o, evtName, evtHandler) {
        return o.detachEvent('on' + evtName, evtHandler);
      }),

      preventDefault: function(e) {
        if (e.preventDefault) {
          e.preventDefault();
        } else {
          e.returnValue = false;
          e.cancelBubble = true;
        }

        return false;
      }
    };

    // 点击监听
    function handleClick(e) {
      var evt;
      var target;
      var offset;
      var targetNodeName;
      var index;
      var src;
      var handled;

      evt = (e || window.event);
      target = evt.target || evt.srcElement;

      if (target && target.nodeName) {
        targetNodeName = target.nodeName.toLowerCase();

        // 播放列表触发
        if (targetNodeName === 'li') {
          index = $(target).data('index');
          src = config.data.sources[index].src;

          if (config.data.selectedIndex === index) {
            if (!soundObject) {
              soundObject = makeSound(src);
            }
            soundObject.togglePause();
          } else {
            if (soundManager.canPlayURL(src)) {
              playIndex(index);
            }
          }

          handled = true;
        }

        // 按钮触发
        if (targetNodeName === 'i') {
          var playBtn = config.selecter.button.play.replace('\.', '');
          var volumeBtn = config.selecter.button.volume.replace('\.', '');
          var menuBtn = config.selecter.button.menu.replace('\.', '');
          var targetClassName = $(target).attr('class').toString();

          // 播放按钮
          if (targetClassName.indexOf(playBtn) >= 0) {
            index = config.data.selectedIndex;
            src = config.data.sources[index].src;

            if (!soundObject) {
              soundObject = makeSound(src);
            }

            controller.select(index);
            soundObject.togglePause();

            handled = true;
          }

          // 音量按钮
          if (targetClassName.indexOf(volumeBtn) >= 0) {
            if (soundObject) {
              soundObject.toggleMute();
              instances.button.volume.toggleClass(config.css.mute);
            }

            handled = true;
          }

          // 菜单按钮
          if (targetClassName.indexOf(menuBtn) >= 0) {
            instances.playlist.toggleClass(config.css.menu);

            handled = true;
          }
        }
      }

      if (!handled) {
        // prevent browser fall-through
        return false;
      }
    }

    // 鼠标移动
    function handleShuffle(e) {
      var target;
      var barX;
      var barWidth;
      var x;
      var newPosition;
      var sound;

      target = instances.progress.get(0);
      barX = getOffX(target);
      barWidth = target.offsetWidth;

      x = (e.clientX - barX);

      newPosition = (x / barWidth);

      sound = soundObject;

      if (sound && sound.duration) {

        sound.setPosition(sound.duration * newPosition);

        // a little hackish: ensure UI updates immediately with current position, even if audio is buffering and hasn't moved there yet.
        sound._iO.whileplaying.apply(sound);

      }

      function getOffX(o) {
        var curleft = 0;

        if (o.offsetParent) {
          while (o.offsetParent) {
            curleft += o.offsetLeft;
            o = o.offsetParent;
          }
        } else if (o.x) {
          curleft += o.x;
        }

        return curleft;
      }

      return false;
    }

    // 播放器初始化
    init();

  } // End Player

})(jQuery, this);
