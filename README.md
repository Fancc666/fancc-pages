# FANCC Pages

## Svelte + PHP + SQLite

项目使用Svelte生成界面，PHP作为后端，SQLite作为数据库。

需要Nginx+PHP环境启动，应当部署在/pages路径。

## 从模板新建数据库

```bash
cp ./dist/userdata0.db ./dist/userdata.db
```

## 构建

```bash
pnpm install
pnpm build
```

2026年8月
