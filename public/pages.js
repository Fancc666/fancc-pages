/**
 * FANCC Pages
 * All rights reserved
 * 
 * 20260817
 */

const tip = document.getElementById("tip");
const date = document.getElementById("date");
const content = document.getElementById("content");
const author = document.getElementById("author");
function getparam(key) {
    let u = new URL(window.location.href);
    let params = new URLSearchParams(u.search);
    return params.get(key);
}
let path = getparam("p");
const host = (location.hostname === "localhost" || location.hostname === "127.0.0.1") ?
    "http://kvm6.tjucloud.sbs" : "";
if (path) {
    path = path.trim();
    document.title = path + " - FANCC Pages";
    start_render();
} else {
    loading.remove();
    tip.innerText = "错误：未输入路径";
}
function start_render() {
    fetch(`${host}/pages/getcontent.php?p=${path}`)
        .then(r => r.json())
        .then(j => {
            if (j.code) {
                tip.innerText = `错误[${j.code}]：${j.msg}`;
                return;
            }
            tip.parentElement.style.display = "none";
            load_content(j.data);
        })
        .catch(e => {
            tip.innerText = "网络错误，请重试。"
        })
        .finally(() => {
            loading.remove();
        })
}
const inlineMathExtension = {
    extensions: [
        {
            name: 'inlineMath',
            level: 'inline',
            start(src) {
                return src.indexOf('$');
            },
            tokenizer(src) {
                const match = src.match(/^\$([^$\n]+?)\$/);
                if (match) {
                    return {
                        type: 'inlineMath',
                        raw: match[0],
                        text: match[1]
                    };
                }
            },
            renderer(token) {
                return katex.renderToString(token.text, {
                    throwOnError: false,
                    displayMode: false
                });
            }
        }
    ]
};
function load_content(j) {
    console.log(j);
    author.innerText = j.uname;
    date.innerText = j.created_at;
    marked.use(markedKatex({
        throwOnError: false
    }));
    marked.use(inlineMathExtension);
    const rawHtml = marked.parse(j.content);
    const cleanHtml = DOMPurify.sanitize(rawHtml, {
        FORBID_TAGS: ['script', 'iframe', 'object', 'embed', 'form', 'input', 'textarea', 'style'],
        FORBID_ATTR: ['onerror', 'onload', 'onclick']
    });
    content.innerHTML = cleanHtml;
    // reset title
    const parser = new DOMParser();
    const doc = parser.parseFromString(cleanHtml, 'text/html');
    const firstH1 = doc.querySelector('h1');
    if (firstH1) {
        document.title = firstH1.innerText;
    }
}
