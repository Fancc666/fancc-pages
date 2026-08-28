<script lang="ts">
  const host =
    location.hostname === "localhost" || location.hostname === "127.0.0.1"
      ? "http://kvm6.tjucloud.sbs"
      : "";

  const placeholder = `# 开始上传吧

## 你可以使用二级标题

也可以用数学公式$E=mc^2$

插入图片![img](url)

每段字之间需要有\`两个空行\`

此功能无需注册，但请注意：**不要上传任何隐私信息**！

\`\`\`
代码块也支持的哦
\`\`\``;

  interface ApiResponse<T> {
    code: number,
    msg: string,
    data: T | null,
  }

  interface UploadResult {
    path: string,
    sec: string,
  }

  interface DeleteResult {
    affected: number,
  }

  interface UpdateCheckResult {
    id: number,
    uname: string,
    content: string,
    created_at: string,
  }

  type RequestStatus<T> =
    | { state: "idle" }
    | { state: "loading" }
    | { state: "success"; result: T }
    | { state: "failed"; code: number; msg: string }
    | { state: "network-error" };

  let content = $state("");
  let uname = $state("");
  let state1 = $state<RequestStatus<UploadResult>>({ state: "idle" });
  let error1 = $state(""); // 表单校验错误
  let ucode = $state("");
  let usec = $state("");
  let state2 = $state<RequestStatus<DeleteResult>>({ state: "idle" });
  let state3 = $state<RequestStatus<UpdateCheckResult>>({ state: "idle" });
  let error2 = $state(""); // 表单校验错误
  let error3 = $state(""); // 表单校验错误
  let uploadMode = $state(0);

  /** 链接只在成功后存在，用 $derived 派生，不用手工同步三个变量 */
  const links = $derived.by(() => {
    if (state1.state !== "success") return null;
    const q = "?p=" + state1.result.path;
    return {
      campus: "http://kvm6.tjucloud.sbs/pages/" + q,
      external: "https://kvm6.happyfan.cc.cd/pages/" + q,
      short: "./" + q,
    };
  });

  async function try_upload() {
    if (content.trim() === "") {
      error1 = "请输入内容";
      return;
    }
    if (content.length > 8000) {
      error1 = "您的markdown文本过长(>8000)";
      return;
    }
    if (uname.trim().length > 30) {
      error1 = "您的署名文本过长(>30)";
      return;
    }

    error1 = "";
    state1 = { state: "loading" };
    if (uploadMode === 0) {
      try {
        const res = await fetch(`${host}/pages/upload.php`, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({ content, uname: uname.trim() || "匿名" }),
        });
        const json: ApiResponse<UploadResult> = await res.json();
        if (json.code || !json.data) {
          state1 = { state: "failed", code: json.code, msg: json.msg };
          return;
        }
        state1 = { state: "success", result: json.data };
      } catch {
        state1 = { state: "network-error" };
      }
    } else if (uploadMode === 1) {
      try {
        const res = await fetch(`${host}/pages/update.php`, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({ content, sec: usec, uname: uname.trim() || "匿名" }),
        });
        const json: ApiResponse<[]> = await res.json();
        if (json.code) {
          state1 = { state: "failed", code: json.code, msg: json.msg };
          return;
        }
        state1 = { state: "idle" }
        error1 = "您已成功修改内容，请使用原链接。";
      } catch {
        state1 = { state: "network-error" };
      }
    }
  }

  async function try_delete() {
    if (ucode.trim().length === 0) {
      error2 = "请输入操作码";
      return;
    }
    
    error2 = "";
    state2 = { state: "loading" };
    try {
      const res = await fetch(
        `${host}/pages/deletecontent.php?` +
          new URLSearchParams({ sec: ucode.trim() }).toString(),
      );
      const json: ApiResponse<DeleteResult> = await res.json();
      if (json.code || !json.data) {
        state2 = { state: "failed", code: json.code, msg: json.msg };
        return;
      }
      state2 = { state: "success", result: json.data };
    } catch {
      state2 = { state: "network-error" };
    }
  }

  async function try_getbysec() {
    if (usec.trim().length === 0) {
      error3 = "请输入操作码";
      return;
    }
    
    error3 = "";
    state3 = { state: "loading" };
    try {
      const res = await fetch(
        `${host}/pages/update.php?` +
          new URLSearchParams({ sec: usec.trim() }).toString(),
      );
      const json: ApiResponse<UpdateCheckResult> = await res.json();
      if (json.code || !json.data) {
        state3 = { state: "failed", code: json.code, msg: json.msg };
        return;
      }
      state3 = { state: "success", result: json.data };
      content = state3.result.content;
      uname = state3.result.uname;
    } catch {
      state3 = { state: "network-error" };
    }
  }
</script>

<style scoped>
  .mode {
    margin: 10px 0;
  }
  .mode > span {
    user-select: none;
    cursor: pointer;
    margin-right: 2em;
    color: black;
    text-decoration: none;
  }
  .mode > span.active {
    color: blue;
    text-decoration: underline;
  }
</style>

<div class="block">
  <h2>上传您的Markdown</h2>

  <div class="mode">
    <span class:active={uploadMode === 0} onclick={()=>{uploadMode = 0}}>新建模式</span>
    <span class:active={uploadMode === 1} onclick={()=>{uploadMode = 1}}>修改模式</span>
  </div>

  {#if uploadMode === 1}
    <form
      onsubmit={(e) => {
        e.preventDefault();
        try_getbysec();
      }}
    >
      <div style="margin: 10px 0;">
        <span>查看现有内容</span>
        <input type="text" placeholder="操作码" bind:value={usec} />
        <button type="submit" disabled={state3.state === "loading"}>
          {state3.state === "loading" ? "请稍候..." : "查看"}
        </button>
        {#if error3}
          <p class="error">{error3}</p>
        {/if}
      </div>
    </form>
    {#if state3.state === "failed"}
      <p class="error">ERROR[{state3.code}]: {state3.msg}</p>
    {:else if state3.state === "network-error"}
      <p class="error">网络错误，请稍后重试</p>
    {/if}
  {/if}

  <form
    onsubmit={(e) => {
      e.preventDefault();
      try_upload();
    }}
  >
    <textarea bind:value={content} {placeholder}></textarea>
    <span>您的署名</span>
    <input type="text" placeholder="匿名" bind:value={uname} />

    {#if state1.state !== "success"}
      <button type="submit" disabled={state1.state === "loading"}>
        {state1.state === "loading" ? "请稍候..." : "提交"}
      </button>
    {/if}
  </form>

  {#if error1}
    <p class="error">{error1}</p>
  {/if}

  {#if state1.state === "success" && links}
    <div style="border: 1px solid black; padding: 4px; margin: 8px 0;">
      <p style="font-weight: bold;">上传成功</p>
      <p>校内访问 <a href={links.campus}>{links.campus}</a></p>
      <p>校外访问 <a href={links.external}>{links.external}</a></p>
      <p style="color: red;">文章操作码（请妥善保存）{state1.result.sec}</p>
    </div>
  {:else if state1.state === "failed"}
    <p class="error">ERROR[{state1.code}]: {state1.msg}</p>
  {:else if state1.state === "network-error"}
    <p class="error">网络错误，请稍后重试</p>
  {/if}
</div>

<div class="block">
  <h2>删除您的文章</h2>

  <form
    onsubmit={(e) => {
      e.preventDefault();
      try_delete();
    }}
  >
    <span>您的操作码</span>
    <input type="text" placeholder="操作码" bind:value={ucode} />

    <button type="submit" disabled={state2.state === "loading"}>
      {state2.state === "loading" ? "请稍候..." : "提交"}
    </button>
  </form>

  {#if error2}
    <p class="error">{error2}</p>
  {/if}

  {#if state2.state === "success"}
    <p style="color: green;">此操作删除了{state2.result.affected}篇文章</p>
  {:else if state2.state === "failed"}
    <p class="error">ERROR[{state2.code}]: {state2.msg}</p>
  {:else if state2.state === "network-error"}
    <p class="error">网络错误，请稍后重试</p>
  {/if}
</div>

<div class="block">
  <h2>特性说明</h2>
  <p>
    欢迎使用FANCC
    Pages，您可以在这里上传markdown文件，系统会生成两个链接和一个操作码，供您在校内/校外访问渲染好的文章和管理文章。
  </p>
  <p>目前支持行内和占一整行的Latex公式渲染，您可使用$xxx$和$$xxx$$插入公式。</p>
  <p>文字分段请插入两个换行符，否则会渲染到一行去。</p>
  <p>
    支持您通过标准语法使用插入图片功能和代码块功能（暂时无法着色），同时禁用了iframe以及script等危险标签。
  </p>
  <p>Markdown限制8000字符，署名限制30字符。</p>
  <p>加载成功后，页面的标签title将是您第一个h1标签的值。</p>
  <p>
    您在校内可以使用（不支持https）<a
      href="http://kvm6.tjucloud.sbs/pages/set.html"
      >http://kvm6.tjucloud.sbs/pages/set.html</a
    >
  </p>
  <p>
    在校外可以使用这个链接上传文章（使用https）<a
      href="https://kvm6.happyfan.cc.cd/pages/set.html"
      >https://kvm6.happyfan.cc.cd/pages/set.html</a
    >
  </p>
  <p>技术支持请联系fancc@565455.xyz</p>
  <p>Made with LOVE, at TJU. 开源于<a href="https://github.com/Fancc666/fancc-pages">Github</a>.</p>
  <p>By FANCC, 2026年8月</p>
</div>
