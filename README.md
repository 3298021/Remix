## 简介 ##

一个简洁清新的 Typecho 播放器插件，需要 Memcache 缓存服务支持。

## 使用 ##

### 参数 ###

 - auto: 自动播放，值有 1 或 0
 - loop: 循环播放，值有 1 或 0
 - type: 列表类型，值有 song、list、album、collect
 - songs: 单曲、专辑、精选集的 id，类型列表时为列表中歌曲的 id

### 示例 ###

 - 单曲: `[Minty auto=0 loop=1 type=song songs=2086679]`
 - 列表: `[Minty auto=0 loop=1 type=list songs=2039856,2086679,1298289]`
 - 专辑: `[Minty auto=0 loop=1 type=album songs=12019827]`
 - 精选集: `[Minty auto=0 loop=1 type=collect songs=4406118]`

## 感谢 ##

 - [SoundManager2](https://github.com/scottschiller/SoundManager2) 播放器内核
 - [Hermit](https://github.com/iMuFeng/Hermit) 提供了 *** 的API
