# 部署与运维手册（中文）
这份文档用于解决“改了很多次后，不知道如何部署”的问题。
后续请只按本文档和 `scripts/linux/` 执行，不要再手工拼零散命令。

## 1. 生产目录约定
- Laravel 目录：`/www/wwwroot/vector.533133.xyz`
- Python 引擎目录：`/www/wwwroot/vector-binary-decoder-master/python_engine`

## 2. 本地仓库哪些内容需要上传
- `php_api/`：覆盖到 Laravel 项目目录
- `python_engine/`：覆盖到引擎目录
- `scripts/linux/`：运维脚本，建议一起上传

## 3. 一次性安装（开机自启）
在服务器执行：

```bash
cd <repo-root>
chmod +x scripts/linux/*.sh
bash scripts/linux/install_autostart_services.sh
```

安装后会创建并启动：
- Redis 依赖服务（自动识别 `redis-server` / `redis` / `redis-local`）
- `vector-queue-worker.service`
- `vector-engine.service`

## 3.5 GitHub 一键部署（推荐）
在服务器 root 下执行：

```bash
REPO_URL=https://github.com/Raniazcwjyj/vector-binary-decoder.git BRANCH=main bash scripts/linux/deploy_from_github.sh
```

如果服务器上还没有仓库文件，可直接远程执行：

```bash
curl -fsSL https://raw.githubusercontent.com/Raniazcwjyj/vector-binary-decoder/main/scripts/linux/deploy_from_github.sh \
| REPO_URL=https://github.com/Raniazcwjyj/vector-binary-decoder.git BRANCH=main bash
```

该脚本会自动执行：拉代码、覆盖部署、安装依赖、清缓存、迁移、重装/重启服务、健康检查。

## 4. 每次发布后的固定流程
```bash
cd /www/wwwroot/vector.533133.xyz
php artisan optimize:clear
bash <repo-root>/scripts/linux/restart_stack.sh
bash <repo-root>/scripts/linux/healthcheck_stack.sh
```

## 5. 常见问题处理
Redis 恢复后，补投“停电期间未入队”的任务：

```bash
bash scripts/linux/requeue_pending_tasks.sh
```

下载提示 `Result file not found`：

```bash
bash scripts/linux/recover_missing_results.sh
bash scripts/linux/fix_permissions.sh
```

## 6. 验收标准
执行：

```bash
bash scripts/linux/healthcheck_stack.sh
```

满足以下条件即为正常：
- `vector-queue-worker` 为 `active`
- `vector-engine` 为 `active`
- Redis 返回 `PONG`
- 引擎健康检查返回 `{"ok":true,...}`

以上都正常时，上传、转换、下载链路可用。
