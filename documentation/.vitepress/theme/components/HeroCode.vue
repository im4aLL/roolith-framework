<script setup>
import { ref } from 'vue'

const tabs = ['routes.php', 'UserController.php', 'User.php', 'validation.php', 'show.php']
const active = ref(0)
</script>

<template>
  <div class="hero-code">
    <div class="hero-code-window">
      <div class="hero-code-bar">
        <div class="dots">
          <span class="dot red"></span>
          <span class="dot yellow"></span>
          <span class="dot green"></span>
        </div>
        <div class="tabs">
          <button
            v-for="(tab, i) in tabs"
            :key="tab"
            type="button"
            class="tab"
            :class="{ active: active === i }"
            @click="active = i"
          >
            {{ tab }}
          </button>
        </div>
      </div>

      <pre v-show="active === 0" class="code"><code><span class="v">$router</span><span class="o">-></span><span class="f">get</span>(<span class="s">'/users/{id}'</span>, <span class="cls">UserController</span><span class="o">::</span><span class="k">class</span><span class="o">.</span><span class="s">'@show'</span>)
    <span class="o">-></span><span class="f">middleware</span>(<span class="cls">AuthMiddleware</span><span class="o">::</span><span class="k">class</span>)
    <span class="o">-></span><span class="f">name</span>(<span class="s">'users.show'</span>);</code></pre>

      <pre v-show="active === 1" class="code"><code><span class="k">public function</span> <span class="f">show</span>(<span class="v">$id</span>)
{
    <span class="v">$user</span> = <span class="cls">User</span><span class="o">::</span><span class="f">orm</span>()-><span class="f">find</span>(<span class="v">$id</span>);

    <span class="k">return</span> <span class="v">$this</span><span class="o">-></span><span class="f">view</span>(<span class="s">'users/show'</span>, [<span class="s">'user'</span> => <span class="v">$user</span>]);
}</code></pre>

      <pre v-show="active === 2" class="code"><code><span class="k">class</span> <span class="cls">User</span> <span class="k">extends</span> <span class="cls">Model</span>
{
    <span class="k">protected</span> <span class="v">$table</span> = <span class="s">'users'</span>;
}

<span class="cls">User</span><span class="o">::</span><span class="f">orm</span>()-><span class="f">where</span>(<span class="s">'name'</span>, <span class="s">'%Hadi%'</span>, <span class="s">'LIKE'</span>)-><span class="f">get</span>();</code></pre>

      <pre v-show="active === 3" class="code"><code><span class="v">$validator</span>-><span class="f">check</span>(<span class="cls">Request</span><span class="o">::</span><span class="f">all</span>(), [
    <span class="s">'email'</span> => <span class="cls">Rules</span><span class="o">::</span><span class="f">set</span>()-><span class="f">isEmail</span>()-><span class="f">isRequired</span>(),
    <span class="s">'name'</span>  => <span class="cls">Rules</span><span class="o">::</span><span class="f">set</span>()-><span class="f">isRequired</span>()-><span class="f">minLength</span>(<span class="n">3</span>),
]);

<span class="k">if</span> (<span class="v">$validator</span>-><span class="f">success</span>()) {
    <span class="cmt">// do something</span>
}</code></pre>

      <pre v-show="active === 4" class="code"><code><span class="o">&lt;?php</span> <span class="v">$this</span><span class="o">-></span><span class="f">inject</span>(<span class="s">'partials/header'</span>) <span class="o">?&gt;</span>

<span class="t">&lt;h1&gt;</span><span class="o">&lt;?=</span> <span class="v">$user</span><span class="o">-></span>name <span class="o">?&gt;</span><span class="t">&lt;/h1&gt;</span>
<span class="t">&lt;a</span> <span class="a">href</span>=<span class="s">"&lt;?= route('users.index') ?&gt;"</span><span class="t">&gt;</span>Back<span class="t">&lt;/a&gt;</span>

<span class="o">&lt;?php</span> <span class="v">$this</span><span class="o">-></span><span class="f">inject</span>(<span class="s">'partials/footer'</span>) <span class="o">?&gt;</span></code></pre>

      <div class="hero-code-status">
        <span class="status-left"><span class="pulse"></span>php 8.0+</span>
        <span class="status-right">roolith/framework</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.hero-code {
  position: relative;
  width: 100%;
  max-width: 800px;
  margin: 0 auto;
  text-align: left;
}

.hero-code-window {
  position: relative;
  border: 1px solid var(--r-code-border);
  border-radius: 14px;
  background: var(--r-code-bg);
  box-shadow: 0 28px 56px -28px rgba(12, 12, 26, 0.55);
  overflow: hidden;
  animation: hero-float 7s ease-in-out infinite;
}

.hero-code-bar {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 10px 14px;
  border-bottom: 1px solid var(--r-code-border);
  background: rgba(255, 255, 255, 0.02);
}

.dots {
  display: flex;
  gap: 6px;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.dot.red {
  background: #ff5f57;
}

.dot.yellow {
  background: #febc2e;
}

.dot.green {
  background: #28c840;
}

.tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.tab {
  border: 1px solid transparent;
  border-radius: 6px;
  padding: 3px 10px;
  font-size: 12px;
  font-family: var(--vp-font-family-mono);
  color: var(--r-code-dim);
  background: transparent;
  cursor: pointer;
  transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
}

.tab:hover {
  color: var(--r-code-text);
}

.tab.active {
  color: var(--r-code-text);
  background: rgba(157, 140, 244, 0.16);
  border-color: rgba(157, 140, 244, 0.35);
}

.code {
  margin: 0;
  padding: 24px 24px;
  font-size: 15px;
  line-height: 1.8;
  font-family: var(--vp-font-family-mono);
  color: var(--r-code-text);
  overflow-x: auto;
}

.code .k {
  color: var(--r-code-keyword);
}

.code .f {
  color: var(--r-code-func);
}

.code .s {
  color: var(--r-code-string);
}

.code .v {
  color: var(--r-code-var);
}

.code .o {
  color: var(--r-code-op);
}

.code .cls {
  color: var(--r-code-class);
}

.code .n {
  color: var(--r-code-number);
}

.code .cmt {
  color: var(--r-code-dim);
  font-style: italic;
}

.code .t {
  color: var(--r-code-tag);
}

.code .a {
  color: var(--r-code-func);
}

.hero-code-status {
  display: flex;
  justify-content: space-between;
  padding: 8px 14px;
  border-top: 1px solid var(--r-code-border);
  font-family: var(--vp-font-family-mono);
  font-size: 11px;
  color: var(--r-code-dim);
}

.pulse {
  display: inline-block;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #28c840;
  margin-right: 6px;
  animation: pulse 2.4s infinite;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(40, 200, 64, 0.45);
  }
  70% {
    box-shadow: 0 0 0 8px rgba(40, 200, 64, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(40, 200, 64, 0);
  }
}

@keyframes hero-float {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-7px);
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-code-window {
    animation: none;
  }

  .pulse {
    animation: none;
  }
}
</style>
