# Future Gadget Lab API 文档

Base URL: `http://localhost:8012/api`

> El Psy Kongroo - 命运石之门社区后端接口

---

## JWT认证说明

本API使用JWT (JSON Web Token) 进行身份认证。

### 获取Token

通过登录接口 `POST /api/users/login` 成功后，响应数据中会包含 `token` 字段。

### 使用Token

在需要认证的请求中，需要在HTTP头部添加：
```
Authorization: Bearer <your_token>
```

### Token有效期

Token有效期为24小时，过期后需重新登录获取。

### 需要JWT认证的接口

| 接口 | 说明 |
|------|------|
| GET /api/users | 获取所有Labmem |
| GET /api/users/{id} | 获取单个Labmem信息 |
| PUT /api/users/{id} | 更新Labmem信息 |
| DELETE /api/users/{id} | 移除Labmem |
| POST /api/upload-image | 图床上传（图片） |
| POST /api/upload-file | 通用文件上传 |
| GET /api/images | 列出图片 |
| GET /api/images/presign | 生成临时访问链接 |
| GET /api/users/search | 搜索Labmem（添加好友用）|
| GET /api/friends | 获取好友列表 |
| POST /api/friends/request | 发送好友请求 |
| GET /api/friends/requests | 获取待处理的好友请求 |
| POST /api/friends/{id}/accept | 接受好友请求 |
| POST /api/friends/{id}/reject | 拒绝好友请求 |
| DELETE /api/friends/{friendId} | 删除好友 |
| POST /api/private-chat/send | 发送私聊消息 |
| GET /api/private-chat/history | 获取私聊历史消息 |
| GET /api/channels | 获取公开频道列表 |
| GET /api/channels/mine | 获取我创建的频道 |
| POST /api/channels | 创建新频道 |
| GET /api/channels/{name} | 获取频道详情 |
| GET /api/stickers | 获取我的表情列表 |
| GET /api/stickers/system | 获取系统表情 |
| GET /api/stickers/public | 获取公开表情（广场）|
| POST /api/stickers | 上传新表情 |
| POST /api/stickers/{id}/collect | 收藏表情 |
| DELETE /api/stickers/{id}/collect | 取消收藏 |
| DELETE /api/stickers/{id} | 删除自己的表情 |

### 不需要JWT认证的接口

| 接口 | 说明 |
|------|------|
| POST /api/users | 注册新Labmem |
| POST /api/users/login | Labmem登录 |
| POST /api/users/check-username | 检查Labmem代号是否可用 |

### 认证失败响应

**未提供Token**
```json
{
    "code": 401,
    "message": "未提供认证令牌，请先登录Future Gadget Lab",
    "data": null
}
```

**Token已失效**
```json
{
    "code": 401,
    "message": "认证令牌已失效，请重新登录",
    "data": null
}
```

---

## Labmem管理 (Users)

### 1. 获取所有Labmem 🔒

> 需要JWT认证

**请求**
```
GET /api/users
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 200,
    "message": "El Psy Kongroo",
    "data": [
        {
            "id": 1,
            "name": "用戶名",
            "email": "user@example.com",
            "gender": "male",
            "avatar": "/uploads/avatars/1.jpg",
            "contact": "13800138000",
            "created_at": "2025-12-22T10:00:00.000000Z",
            "updated_at": "2025-12-22T10:00:00.000000Z"
        }
    ]
}
```

---

### 2. 注册新Labmem 🔓

> 无需JWT认证

**请求**
```
POST /api/users
Content-Type: application/json
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | ✅ | Labmem代号 |
| password | string | ✅ | D-Mail密钥（最少6位）|
| email | string | ❌ | 联络邮箱 |
| gender | string | ❌ | 性别：male / female / other |
| avatar | string | ❌ | 头像路径 |
| contact | string | ❌ | 联系方式 |

**请求示例**
```json
{
    "name": "凤凰院凶真",
    "password": "ElPsyKongroo",
    "email": "kyouma@futuregadgetlab.com",
    "gender": "male",
    "contact": "13800138000"
}
```

**响应**
```json
{
    "code": 201,
    "message": "欢迎加入Future Gadget Lab，Labmem注册完成",
    "data": {
        "id": 1,
        "name": "凤凰院凶真",
        "email": "kyouma@futuregadgetlab.com",
        "gender": "male",
        "avatar": null,
        "contact": "13800138000",
        "created_at": "2025-12-22T10:00:00.000000Z",
        "updated_at": "2025-12-22T10:00:00.000000Z"
    }
}
```

---

### 3. 获取单个Labmem信息 🔒

> 需要JWT认证

**请求**
```
GET /api/users/{id}
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 200,
    "message": "El Psy Kongroo",
    "data": {
        "id": 1,
        "name": "凤凰院凶真",
        "email": "kyouma@futuregadgetlab.com",
        "gender": "male",
        "avatar": null,
        "contact": "13800138000",
        "created_at": "2025-12-22T10:00:00.000000Z",
        "updated_at": "2025-12-22T10:00:00.000000Z"
    }
}
```

**错误响应（Labmem不存在）**
```json
{
    "code": 404,
    "message": "该Labmem不存在于此世界线",
    "data": null
}
```

---

### 4. 更新Labmem信息 🔒

> 需要JWT认证

**请求**
```
PUT /api/users/{id}
Content-Type: application/json
Authorization: Bearer <token>
```

**参数**（所有参数可选，只传需要更新的字段）
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | ❌ | Labmem代号 |
| password | string | ❌ | D-Mail密钥（最少6位）|
| email | string | ❌ | 联络邮箱 |
| gender | string | ❌ | 性别：male / female / other |
| avatar | string | ❌ | 头像路径 |
| contact | string | ❌ | 联系方式 |

**请求示例**
```json
{
    "name": "助手",
    "email": "mayuri@futuregadgetlab.com"
}
```

**响应**
```json
{
    "code": 200,
    "message": "Labmem信息已在世界线中更新",
    "data": {
        "id": 1,
        "name": "助手",
        "email": "mayuri@futuregadgetlab.com",
        "gender": "male",
        "avatar": null,
        "contact": "13800138000",
        "created_at": "2025-12-22T10:00:00.000000Z",
        "updated_at": "2025-12-22T10:30:00.000000Z"
    }
}
```

---

### 5. 移除Labmem 🔒

> 需要JWT认证

**请求**
```
DELETE /api/users/{id}
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 200,
    "message": "Labmem已从此世界线消失",
    "data": null
}
```

---

### 6. Labmem登录 🔓

> 无需JWT认证

**请求**
```
POST /api/users/login
Content-Type: application/json
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | ✅ | Labmem代号 |
| password | string | ✅ | D-Mail密钥 |

**请求示例**
```json
{
    "name": "凤凰院凶真",
    "password": "ElPsyKongroo"
}
```

**成功响应**
```json
{
    "code": 200,
    "message": "世界线变动率确认，欢迎回来Labmem",
    "data": {
        "user": {
            "id": 1,
            "name": "凤凰院凶真",
            "email": "kyouma@futuregadgetlab.com",
            "gender": "male",
            "avatar": null,
            "contact": "13800138000",
            "created_at": "2025-12-22T10:00:00.000000Z",
            "updated_at": "2025-12-22T10:00:00.000000Z"
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
    }
}
```

**错误响应（Labmem不存在）**
```json
{
    "code": 404,
    "message": "该Labmem不存在于此世界线",
    "data": null
}
```

**错误响应（D-Mail密钥错误）**
```json
{
    "code": 401,
    "message": "认证失败，D-Mail密钥不匹配",
    "data": null
}
```

---

### 7. 检查Labmem代号是否可用 🔓

> 无需JWT认证

**请求**
```
POST /api/users/check-username
Content-Type: application/json
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | ✅ | Labmem代号 |

**请求示例**
```json
{
    "name": "凤凰院凶真"
}
```

**成功响应（代号可用）**
```json
{
    "code": 200,
    "message": "该代号可用，欢迎加入Future Gadget Lab",
    "data": null
}
```

**错误响应（代号已存在）**
```json
{
    "code": 409,
    "message": "此世界线已存在同名Labmem，请更换代号",
    "data": null
}
```

---

## 世界线状态码说明

| 状态码 | 说明 |
|--------|------|
| 200 | El Psy Kongroo - 操作成功 |
| 201 | Labmem注册完成 / 上传完成 |
| 401 | D-Mail密钥不匹配 |
| 404 | Labmem不存在于此世界线 |
| 409 | 世界线冲突（代号已存在）|
| 422 | 参数错误（无效的输入）|
| 500 | 世界线异常（服务器错误）|

---

## 文件上传（Cloudflare R2）

> 采用用户隔离方案，每个用户的文件存储在 `users/{user_id}/` 前缀下，路径由后端自动生成，前端无法控制。
>
> **存储路径规范：**
> - 图片：`users/{user_id}/{uuid}.{ext}`
> - 通用文件：`users/{user_id}/files/{uuid}.{ext}`

### 上传图片 🔒

> 需要JWT认证

POST /api/upload-image

- Content-Type: multipart/form-data
- Authorization: Bearer <token>
- 字段：
  - file: 图片文件（必填，jpg/jpeg/png/gif/webp/avif，≤50MB）

示例

curl -i -X POST https://api.mahoer.space/api/upload-image \
  -H "Authorization: Bearer <token>" \
  -F "file=@/path/to/image.jpg"

成功响应

{
  "code": 201,
  "message": "金属乌帕已穿越至R2世界线",
  "data": {
    "key": "users/3/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.jpg",
    "mime": "image/jpeg",
    "size": 123456,
    "url": "https://<你的公共域名>/users/3/xxxx.jpg"
  }
}

---

### 上传通用文件 🔒

> 需要JWT认证
> 支持所有文件类型（视频/音频/文档/压缩包等），用于聊天发送文件等场景

POST /api/upload-file

- Content-Type: multipart/form-data
- Authorization: Bearer <token>
- 字段：
  - file: 文件（必填，≤100MB）

示例

curl -i -X POST https://api.mahoer.space/api/upload-file \
  -H "Authorization: Bearer <token>" \
  -F "file=@/path/to/document.pdf"

成功响应

{
  "code": 201,
  "message": "文件已传输至R2世界线",
  "data": {
    "key": "users/3/files/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.pdf",
    "name": "document.pdf",
    "mime": "application/pdf",
    "size": 123456,
    "url": "https://<你的公共域名>/users/3/files/xxxx.pdf"
  }
}

---

### 列出图片 🔒

> 需要JWT认证
> 自动列出当前用户的所有图片，无需指定目录

GET /api/images?per_page=50&page=1
Authorization: Bearer <token>

响应

{
  "code": 200,
  "message": "El Psy Kongroo",
  "data": {
    "total": 10,
    "page": 1,
    "per_page": 50,
    "items": [
      { "key": "users/3/xxx.jpg", "url": "https://<你的公共域名>/users/3/xxx.jpg" },
      { "key": "users/3/yyy.png", "url": "https://<你的公共域名>/users/3/yyy.png" }
    ]
  }
}

---

## 生成临时访问链接（用于私有桶） 🔒

> 需要JWT认证
> 只能访问当前用户自己的图片

GET /api/images/presign?key=users/3/xxx.jpg&expires=900
Authorization: Bearer <token>

响应

{
  "code": 200,
  "message": "El Psy Kongroo",
  "data": { "url": "https://签名后的临时地址" }
}

**错误响应（访问他人图片）**

{
  "code": 403,
  "message": "无权访问该资源，世界线干涉被拒绝",
  "data": null
}

---

## 频道管理 (Channels)

> 频道是用户创建的群组聊天室，支持公开和私有两种模式

### 1. 获取公开频道列表 🔒

> 需要JWT认证
> 返回所有公开频道（is_private=false）

**请求**
```
GET /api/channels
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 0,
    "message": "success",
    "data": [
        {
            "id": 1,
            "name": "lobby",
            "display_name": "大厅",
            "description": "默认公共频道",
            "is_default": true,
            "is_private": false,
            "creator_id": null,
            "creator": null,
            "created_at": "2025-12-25T10:00:00.000000Z"
        },
        {
            "id": 2,
            "name": "tech-talk",
            "display_name": "技术讨论",
            "description": "讨论技术问题",
            "is_default": false,
            "is_private": false,
            "creator_id": 1,
            "creator": {
                "id": 1,
                "name": "凤凰院凶真"
            },
            "created_at": "2025-12-25T11:00:00.000000Z"
        }
    ]
}
```

---

### 2. 获取我创建的频道 🔒

> 需要JWT认证
> 返回当前用户创建的所有频道（包括公开和私有）

**请求**
```
GET /api/channels/mine
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 0,
    "message": "success",
    "data": [
        {
            "id": 2,
            "name": "my-channel",
            "display_name": "我的频道",
            "description": "私人频道",
            "is_default": false,
            "is_private": true,
            "creator_id": 1,
            "creator": {
                "id": 1,
                "name": "凤凰院凶真"
            },
            "created_at": "2025-12-25T11:00:00.000000Z"
        }
    ]
}
```

---

### 3. 创建新频道 🔒

> 需要JWT认证

**请求**
```
POST /api/channels
Content-Type: application/json
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | ✅ | 频道名称（小写字母、数字、下划线、短横线，最夔50字）|
| display_name | string | ✅ | 显示名称（最多100字）|
| description | string | ❌ | 频道描述（最多500字）|
| is_private | boolean | ❌ | 是否私有频道，默认false |

**请求示例**
```json
{
    "name": "my-channel",
    "display_name": "我的频道",
    "description": "这是我创建的频道",
    "is_private": false
}
```

**成功响应**
```json
{
    "code": 0,
    "message": "Channel created successfully",
    "data": {
        "id": 3,
        "name": "my-channel",
        "display_name": "我的频道",
        "description": "这是我创建的频道",
        "is_default": false,
        "is_private": false,
        "creator_id": 1,
        "creator": {
            "id": 1,
            "name": "凤凰院凶真"
        },
        "created_at": "2025-12-25T12:00:00.000000Z"
    }
}
```

**错误响应（频道名已存在）**
```json
{
    "message": "The name has already been taken.",
    "errors": {
        "name": ["The name has already been taken."]
    }
}
```

---

### 4. 获取频道详情 🔒

> 需要JWT认证

**请求**
```
GET /api/channels/{name}
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 0,
    "message": "success",
    "data": {
        "id": 1,
        "name": "lobby",
        "display_name": "大厅",
        "description": "默认公共频道",
        "is_default": true,
        "is_private": false,
        "creator_id": null,
        "creator": null,
        "created_at": "2025-12-25T10:00:00.000000Z"
    }
}
```

**错误响应（频道不存在）**
```json
{
    "code": 404,
    "message": "Channel not found"
}
```

---

### 5. 更新频道公告 🔒

> 需要JWT认证，仅频道创建者或管理员可操作

**请求**
```
PUT /api/channels/{name}/announcement
Content-Type: application/json
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| announcement | string | ❌ | 频道公告内容（最多1000字，传空字符串清除公告）|

**请求示例**
```json
{
    "announcement": "欢迎来到命运石之门社区！请遵守社区规则。"
}
```

**成功响应**
```json
{
    "code": 0,
    "message": "Announcement updated",
    "data": {
        "id": 1,
        "name": "lobby",
        "display_name": "大厅",
        "description": "默认公共频道",
        "announcement": "欢迎来到命运石之门社区！请遵守社区规则。",
        "is_default": true,
        "is_private": false,
        "creator_id": null,
        "creator": null,
        "created_at": "2025-12-25T10:00:00.000000Z"
    }
}
```

**错误响应（无权限）**
```json
{
    "code": 403,
    "message": "Unauthorized"
}
```

---

## 圆桌会议（实时聊天）

> 使用 Laravel Reverb WebSocket 实现实时通信

### WebSocket 连接

- WebSocket 地址：`wss://ws.mahoer.space`
- 广播认证接口：`POST /api/broadcasting/auth`
- 频道：`presence-lobby`（Presence Channel，可获取在线用户列表）

### 发送消息 🔒

> 需要JWT认证

POST /api/chat/send
Content-Type: application/json
Authorization: Bearer <token>

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| content | string | ✅ | 消息内容（文本或文件URL，最多2000字）|
| channel | string | ✅ | 频道名称 |
| type | string | ❌ | 消息类型：text/image/file/sticker/system，默认text |

**type 类型说明**
| 值 | 说明 | content 内容 |
|------|------|------|
| text | 纯文本消息 | 文本内容 |
| image | 图片消息 | 图片URL |
| file | 文件消息 | 文件URL |
| sticker | 表情消息 | 表情图片URL |
| system | 系统消息 | 系统提示文本 |

**请求示例**
```json
{
    "content": "这就是命运石之门的选择！"
}
```

**成功响应**
```json
{
    "code": 201,
    "message": "D-Mail已发送至世界线",
    "data": {
        "id": 1,
        "content": "这就是命运石之门的选择！",
        "type": "text",
        "created_at": "2025-12-25T10:30:00+08:00",
        "user": {
            "id": 1,
            "name": "凤凰院凶真",
            "avatar": null
        }
    }
}
```

### 获取历史消息 🔒

> 需要JWT认证

GET /api/chat/history?limit=50&before_id=100
Authorization: Bearer <token>

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| limit | int | ❌ | 获取数量，默认50，最大100 |
| before_id | int | ❌ | 获取该ID之前的消息（用于分页）|

**响应**
```json
{
    "code": 200,
    "message": "El Psy Kongroo",
    "data": {
        "items": [
            {
                "id": 1,
                "content": "这就是命运石之门的选择！",
                "type": "text",
                "created_at": "2025-12-25T10:30:00+08:00",
                "user": {
                    "id": 1,
                    "name": "凤凰院凶真",
                    "avatar": null
                }
            }
        ],
        "has_more": false
    }
}
```

### WebSocket 事件

**连接到 lobby 频道后可监听：**

| 事件 | 说明 |
|------|------|
| here | 连接成功时返回当前在线用户列表 |
| joining | 有新用户加入 |
| leaving | 有用户离开 |
| .message.sent | 收到新消息 |

---

## 同行Labmem（好友系统）

> 好友关系采用双向记录模式，每对好友关系在数据库中存储两条记录，便于高效查询

### 1. 搜索Labmem 🔒

> 需要JWT认证
> 用于搜索并添加好友，会排除自己

**请求**
```
GET /api/users/search?q=凤凰院
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| q | string | ✅ | 搜索关键词（1-50字）|

**响应**
```json
{
    "code": 0,
    "message": "success",
    "data": [
        {
            "id": 1,
            "name": "凤凰院凶真",
            "avatar": null,
            "friendship_status": "none"
        }
    ]
}
```

**friendship_status 说明**
| 值 | 说明 |
|------|------|
| none | 非好友，可发送请求 |
| pending | 已向对方发送请求，等待确认 |
| accepted | 已是好友 |
| incoming | 对方已向你发送请求 |

---

### 2. 获取好友列表 🔒

> 需要JWT认证

**请求**
```
GET /api/friends
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 0,
    "message": "success",
    "data": [
        {
            "id": 1,
            "name": "凤凰院凶真",
            "avatar": null,
            "added_at": "2025-12-25T10:00:00+08:00"
        }
    ]
}
```

---

### 3. 发送好友请求 🔒

> 需要JWT认证

**请求**
```
POST /api/friends/request
Content-Type: application/json
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| friend_id | int | ✅ | 目标Labmem的ID |

**请求示例**
```json
{
    "friend_id": 2
}
```

**成功响应**
```json
{
    "code": 0,
    "message": "好友请求已发送"
}
```

**特殊情况：对方也向你发送了请求**
```json
{
    "code": 0,
    "message": "对方也向你发送了请求，已自动成为好友"
}
```

**错误响应**
```json
{
    "code": 400,
    "message": "不能添加自己为好友"
}
```

```json
{
    "code": 400,
    "message": "已经是好友了"
}
```

```json
{
    "code": 400,
    "message": "已发送过好友请求，请等待对方确认"
}
```

---

### 4. 获取待处理的好友请求 🔒

> 需要JWT认证
> 获取别人发给自己的待处理请求

**请求**
```
GET /api/friends/requests
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 0,
    "message": "success",
    "data": [
        {
            "id": 1,
            "user": {
                "id": 2,
                "name": "凤凰院凶真",
                "avatar": null
            },
            "created_at": "2025-12-25T10:00:00+08:00"
        }
    ]
}
```

---

### 5. 接受好友请求 🔒

> 需要JWT认证

**请求**
```
POST /api/friends/{id}/accept
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✅ | 好友请求的ID（来自获取待处理请求接口）|

**成功响应**
```json
{
    "code": 0,
    "message": "已接受好友请求"
}
```

**错误响应**
```json
{
    "code": 404,
    "message": "好友请求不存在或已处理"
}
```

---

### 6. 拒绝好友请求 🔒

> 需要JWT认证

**请求**
```
POST /api/friends/{id}/reject
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✅ | 好友请求的ID（来自获取待处理请求接口）|

**成功响应**
```json
{
    "code": 0,
    "message": "已拒绝好友请求"
}
```

**错误响应**
```json
{
    "code": 404,
    "message": "好友请求不存在或已处理"
}
```

---

### 7. 删除好友 🔒

> 需要JWT认证

**请求**
```
DELETE /api/friends/{friendId}
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| friendId | int | ✅ | 好友的用户ID |

**成功响应**
```json
{
    "code": 0,
    "message": "已删除好友"
}
```

**错误响应**
```json
{
    "code": 404,
    "message": "好友关系不存在"
}
```

---

## 私聊D-Mail（私人通信）

> 基于好友关系的一对一私聊，使用WebSocket实现实时通信
> 私聊频道格式：`private-chat.{conversationId}`
> conversationId由两个用户ID组成：`{较小ID}_{较大ID}`

### WebSocket 私聊频道

- 频道类型：Private Channel
- 频道格式：`private-chat.{conversationId}`
- 例如用户1和用户3的私聊频道：`private-chat.1_3`

### 1. 发送私聊消息 🔒

> 需要JWT认证
> 只能发送给好友

**请求**
```
POST /api/private-chat/send
Content-Type: application/json
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| friend_id | int | ✅ | 好友的用户ID |
| content | string | ✅ | 消息内容（文本或文件URL，最多2000字）|
| type | string | ❌ | 消息类型：text/image/file/sticker/system，默认text |

**type 类型说明**
| 值 | 说明 | content 内容 |
|------|------|------|
| text | 纯文本消息 | 文本内容 |
| image | 图片消息 | 图片URL |
| file | 文件消息 | 文件URL |
| sticker | 表情消息 | 表情图片URL |
| system | 系统消息 | 系统提示文本 |

**请求示例**
```json
{
    "friend_id": 2,
    "content": "El Psy Kongroo"
}
```

**成功响应**
```json
{
    "code": 201,
    "message": "D-Mail已发送",
    "data": {
        "id": 1,
        "conversation_id": "1_2",
        "content": "El Psy Kongroo",
        "type": "text",
        "created_at": "2025-12-25T10:30:00+08:00",
        "sender": {
            "id": 1,
            "name": "凤凰院凶真",
            "avatar": null
        }
    }
}
```

**错误响应**
```json
{
    "code": 403,
    "message": "只能给好友发送私聊消息"
}
```

---

### 2. 获取私聊历史消息 🔒

> 需要JWT认证
> 只能获取好友的聊天记录
> 获取消息时会自动标记为已读

**请求**
```
GET /api/private-chat/history?friend_id=2&limit=50&before_id=100
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| friend_id | int | ✅ | 好友的用户ID |
| limit | int | ❌ | 获取数量，默认50，最大100 |
| before_id | int | ❌ | 获取该ID之前的消息（用于分页）|

**响应**
```json
{
    "code": 200,
    "message": "El Psy Kongroo",
    "data": {
        "items": [
            {
                "id": 1,
                "content": "El Psy Kongroo",
                "type": "text",
                "created_at": "2025-12-25T10:30:00+08:00",
                "sender": {
                    "id": 1,
                    "name": "凤凰院凶真",
                    "avatar": null
                }
            }
        ],
        "has_more": false
    }
}
```

**错误响应**
```json
{
    "code": 403,
    "message": "只能查看好友的聊天记录"
}
```

---

### WebSocket 私聊事件

**监听私聊频道后可接收：**

| 事件 | 说明 |
|------|------|
| .private-message.sent | 收到新的私聊消息 |

**事件数据格式**
```json
{
    "id": 1,
    "conversation_id": "1_2",
    "content": "El Psy Kongroo",
    "type": "text",
    "created_at": "2025-12-25T10:30:00+08:00",
    "sender": {
        "id": 1,
        "name": "凤凰院凶真",
        "avatar": null
    }
}
```

---

## 表情系统 (Stickers)

> 支持系统表情、用户上传表情、收藏他人表情
> 表情存储在 Cloudflare R2

### 1. 获取我的表情列表 🔒

> 需要JWT认证
> 返回系统表情、我上传的表情、我收藏的表情

**请求**
```
GET /api/stickers
Authorization: Bearer <token>
```

**可选参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| category | string | ❌ | 按分类筛选 |

**响应**
```json
{
    "code": 200,
    "data": {
        "system": [
            {
                "id": 1,
                "user_id": null,
                "name": "开心",
                "url": "https://r2.example.com/stickers/happy.png",
                "category": "default",
                "is_public": true
            }
        ],
        "mine": [],
        "collected": []
    }
}
```

---

### 2. 获取系统表情 🔒

> 需要JWT认证
> 按分类分组返回

**请求**
```
GET /api/stickers/system
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 200,
    "data": {
        "default": [
            {
                "id": 1,
                "name": "开心",
                "url": "https://r2.example.com/stickers/happy.png",
                "category": "default"
            }
        ]
    }
}
```

---

### 3. 获取公开表情（广场） 🔒

> 需要JWT认证
> 获取其他用户上传的公开表情，支持分页

**请求**
```
GET /api/stickers/public?page=1&per_page=50
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | ❌ | 页码，默认1 |
| per_page | int | ❌ | 每页数量，默认50 |

**响应**
```json
{
    "code": 200,
    "data": [
        {
            "id": 5,
            "user_id": 2,
            "name": "有趣的表情",
            "url": "https://r2.example.com/users/2/stickers/xyz.png",
            "user": {
                "id": 2,
                "name": "牧濑红莉栖"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "total": 1
    }
}
```

---

### 4. 上传新表情 🔒

> 需要JWT认证
> 表情图片最大 2MB

**请求**
```
POST /api/stickers
Content-Type: multipart/form-data
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| file | file | ✅ | 表情图片文件 |
| name | string | ❌ | 表情名称（默认为文件名）|
| category | string | ❌ | 分类（默认 custom）|
| is_public | boolean | ❌ | 是否公开（默认 true）|

**成功响应**
```json
{
    "code": 201,
    "message": "表情上传成功",
    "data": {
        "id": 10,
        "user_id": 1,
        "name": "我的表情",
        "url": "https://r2.example.com/users/1/stickers/uuid.png",
        "category": "custom",
        "is_public": true
    }
}
```

---

### 5. 收藏表情 🔒

> 需要JWT认证
> 不能收藏自己的表情

**请求**
```
POST /api/stickers/{id}/collect
Authorization: Bearer <token>
```

**成功响应**
```json
{
    "code": 200,
    "message": "收藏成功"
}
```

**错误响应**
```json
{
    "code": 400,
    "message": "不能收藏自己的表情"
}
```

---

### 6. 取消收藏 🔒

> 需要JWT认证

**请求**
```
DELETE /api/stickers/{id}/collect
Authorization: Bearer <token>
```

**成功响应**
```json
{
    "code": 200,
    "message": "取消收藏成功"
}
```

---

### 7. 删除自己的表情 🔒

> 需要JWT认证
> 只能删除自己上传的表情

**请求**
```
DELETE /api/stickers/{id}
Authorization: Bearer <token>
```

**成功响应**
```json
{
    "code": 200,
    "message": "删除成功"
}
```

**错误响应**
```json
{
    "code": 404,
    "message": "表情不存在或无权删除"
}
```

---

## 机器人系统 (Bot)

> 机器人系统提供 AI 驱动的自动聊天功能
> 机器人会根据对话上下文自动回复消息，也可以主动发起聊天

### 1. 获取所有 API 配置 🔒

> 需要JWT认证
> 用于系统内部获取机器人配置

**请求**
```
GET /api/bot/configs
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 200,
    "message": "El Psy Kongroo",
    "data": [
        {
            "id": 1,
            "api_name": "SGC_冈部伦太郎",
            "api_key": "sk-xxx...",
            "user": {
                "id": 10,
                "name": "Okabe_Rintaro",
                "avatar": null,
                "personality": "你是冈部伦太郎...",
                "is_bot": true
            }
        }
    ]
}
```

---

### 机器人行为说明

**自动回复规则：**
| 场景 | 回复概率 |
|------|------|
| 被 @或提及名字 | 95% |
| 看起来是问题 | 40% |
| 问候语 | 60% |
| 普通消息 | 15% |

**冷却机制：**
- 回复后 30 秒内不会再次回复同一频道
- 主动发言后 120 秒内不会再次主动发言

**主动发言：**
- 通过定时任务 `php artisan bot:chat` 触发
- 可配置频道和发言概率

**命令示例：**
```bash
# 单次发言（30%概率）
php artisan bot:chat --channel=lobby --probability=30

# 启动守护进程（随机化发言）
php artisan bot:daemon --channel=lobby --min-interval=60 --max-interval=300

# 带安静时段（23:00-07:00 不发言）
php artisan bot:daemon --quiet-start=23 --quiet-end=7
```

**守护进程参数：**
| 参数 | 默认值 | 说明 |
|------|------|------|
| --channel | lobby | 目标频道 |
| --min-interval | 60 | 最小发言间隔(秒) |
| --max-interval | 300 | 最大发言间隔(秒) |
| --quiet-start | 23 | 安静时段开始小时 |
| --quiet-end | 7 | 安静时段结束小时 |

---

## 观测日志（帖子系统）

> 社区帖子功能，支持发布、点赞、评论、转发

### 1. 获取帖子列表 🔒

> 需要JWT认证

**请求**
```
GET /api/posts?search=时间机器&tag=TECH&limit=20&page=1
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| search | string | ❌ | 搜索关键词（匹配标题和内容） |
| tag | string | ❌ | 标签筛选：THEORY/TECH/MISSION/GENERAL |
| user_id | int | ❌ | 按作者筛选 |
| limit | int | ❌ | 每页数量，默认20，最大50 |
| page | int | ❌ | 页码，默认1 |

**响应**
```json
{
    "code": 200,
    "message": "El Psy Kongroo",
    "data": {
        "items": [
            {
                "id": 1,
                "title": "关于时间机器的理论探讨",
                "content": "内容...",
                "cover": "https://r2.example.com/covers/xxx.jpg",
                "tag": "THEORY",
                "view_count": 100,
                "like_count": 50,
                "comment_count": 10,
                "share_count": 5,
                "is_liked": false,
                "created_at": "2025-12-31T10:00:00.000000Z",
                "user": {
                    "id": 1,
                    "name": "凤凰院凶真",
                    "avatar": null
                }
            }
        ],
        "total": 100,
        "current_page": 1,
        "last_page": 5
    }
}
```

---

### 2. 获取帖子详情 🔒

> 需要JWT认证
> 访问时自动增加浏览量

**请求**
```
GET /api/posts/{id}
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 200,
    "message": "El Psy Kongroo",
    "data": {
        "id": 1,
        "title": "关于时间机器的理论探讨",
        "content": "详细内容...",
        "cover": "https://r2.example.com/covers/xxx.jpg",
        "tag": "THEORY",
        "view_count": 101,
        "like_count": 50,
        "comment_count": 10,
        "share_count": 5,
        "is_liked": true,
        "created_at": "2025-12-31T10:00:00.000000Z",
        "user": {
            "id": 1,
            "name": "凤凰院凶真",
            "avatar": null
        }
    }
}
```

---

### 3. 创建帖子 🔒

> 需要JWT认证

**请求**
```
POST /api/posts
Content-Type: application/json
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| title | string | ✅ | 标题（最多200字）|
| content | string | ✅ | 内容 |
| cover | string | ❌ | 封面图片URL |
| tag | string | ❌ | 标签，默认GENERAL |

**请求示例**
```json
{
    "title": "关于时间机器的理论探讨",
    "content": "我们在实验中发现...",
    "cover": "https://r2.example.com/covers/xxx.jpg",
    "tag": "THEORY"
}
```

**成功响应**
```json
{
    "code": 201,
    "message": "观测日志已记录至世界线",
    "data": {
        "id": 1,
        "title": "关于时间机器的理论探讨",
        "....": "..."
    }
}
```

---

### 4. 更新帖子 🔒

> 需要JWT认证
> 只能更新自己的帖子

**请求**
```
PUT /api/posts/{id}
Content-Type: application/json
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| title | string | ❌ | 标题 |
| content | string | ❌ | 内容 |
| cover | string | ❌ | 封面图片URL |
| tag | string | ❌ | 标签 |

**成功响应**
```json
{
    "code": 200,
    "message": "观测日志已更新",
    "data": { ... }
}
```

**错误响应**
```json
{
    "code": 403,
    "message": "无权修改他人的观测日志"
}
```

---

### 5. 删除帖子 🔒

> 需要JWT认证
> 只能删除自己的帖子

**请求**
```
DELETE /api/posts/{id}
Authorization: Bearer <token>
```

**成功响应**
```json
{
    "code": 200,
    "message": "观测日志已从世界线中移除"
}
```

---

### 6. 点赞/取消点赞 🔒

> 需要JWT认证
> 已点赞则取消，未点赞则点赞

**请求**
```
POST /api/posts/{id}/like
Authorization: Bearer <token>
```

**响应（点赞）**
```json
{
    "code": 200,
    "message": "已赞同",
    "data": {
        "is_liked": true,
        "like_count": 51
    }
}
```

**响应（取消点赞）**
```json
{
    "code": 200,
    "message": "已取消赞同",
    "data": {
        "is_liked": false,
        "like_count": 50
    }
}
```

---

### 7. 获取评论列表 🔒

> 需要JWT认证

**请求**
```
GET /api/posts/{postId}/comments?limit=20&page=1
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| limit | int | ❌ | 每页数量，默认20，最大50 |
| page | int | ❌ | 页码，默认1 |

**响应**
```json
{
    "code": 200,
    "message": "El Psy Kongroo",
    "data": {
        "items": [
            {
                "id": 1,
                "user_id": 1,
                "post_id": 1,
                "parent_id": null,
                "content": "这个理论很有意思！",
                "like_count": 5,
                "is_liked": false,
                "created_at": "2025-12-31T10:00:00.000000Z",
                "user": {
                    "id": 1,
                    "name": "凤凰院凶真",
                    "avatar": null
                },
                "replies": [
                    {
                        "id": 2,
                        "user_id": 2,
                        "content": "同意！",
                        "like_count": 1,
                        "is_liked": true,
                        "user": { ... }
                    }
                ]
            }
        ],
        "total": 10,
        "current_page": 1,
        "last_page": 1
    }
}
```

---

### 8. 发表评论 🔒

> 需要JWT认证

**请求**
```
POST /api/posts/{postId}/comments
Content-Type: application/json
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| content | string | ✅ | 评论内容（最大2000字）|
| parent_id | int | ❌ | 回复的评论ID |

**请求示例**
```json
{
    "content": "这个理论很有意思！",
    "parent_id": null
}
```

**成功响应**
```json
{
    "code": 201,
    "message": "评论已发送至世界线",
    "data": {
        "id": 1,
        "content": "这个理论很有意思！",
        "user": { ... }
    }
}
```

---

### 9. 删除评论 🔒

> 需要JWT认证
> 只能删除自己的评论

**请求**
```
DELETE /api/posts/{postId}/comments/{commentId}
Authorization: Bearer <token>
```

**成功响应**
```json
{
    "code": 200,
    "message": "评论已从世界线中移除"
}
```

---

### 10. 评论点赞/取消点赞 🔒

> 需要JWT认证

**请求**
```
POST /api/posts/{postId}/comments/{commentId}/like
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 200,
    "message": "已赞同",
    "data": {
        "is_liked": true,
        "like_count": 6
    }
}
```

---

## 世界线观测（通知系统）

> 通知系统用于提醒用户点赞、评论、转发、私聊、好友请求等事件

### 通知类型说明

| 类型 | 说明 |
|------|------|
| post_like | 帖子被点赞 |
| comment_like | 评论被点赞 |
| comment | 帖子收到评论 |
| comment_reply | 评论被回复 |
| post_share | 帖子被转发 |
| private_message | 收到私聊消息 |
| friend_request | 收到好友请求 |

---

### 1. 获取通知列表 🔒

> 需要JWT认证

**请求**
```
GET /api/notifications?type=post_like&limit=20&page=1
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | ❌ | 筛选通知类型 |
| limit | int | ❌ | 每页数量，默认20，最大50 |
| page | int | ❌ | 页码，默认1 |

**响应**
```json
{
    "code": 200,
    "message": "El Psy Kongroo",
    "data": {
        "items": [
            {
                "id": 1,
                "user_id": 1,
                "sender_id": 2,
                "type": "post_like",
                "notifiable_type": "App\\Models\\Post",
                "notifiable_id": 10,
                "data": {
                    "post_title": "关于时间机器的理论探讨"
                },
                "read_at": null,
                "created_at": "2025-12-31T10:00:00.000000Z",
                "sender": {
                    "id": 2,
                    "name": "牧濑红莉栖",
                    "avatar": null
                }
            }
        ],
        "total": 50,
        "current_page": 1,
        "last_page": 3,
        "unread_count": 10
    }
}
```

---

### 2. 获取未读通知数量 🔒

> 需要JWT认证

**请求**
```
GET /api/notifications/unread-count
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 200,
    "message": "El Psy Kongroo",
    "data": {
        "total": 15,
        "by_type": {
            "like": 5,
            "comment": 3,
            "share": 2,
            "message": 4,
            "friend": 1
        }
    }
}
```

---

### 3. 标记通知为已读 🔒

> 需要JWT认证
> 可批量标记多个通知

**请求**
```
POST /api/notifications/read
Content-Type: application/json
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| ids | array | ✅ | 通知ID数组 |

**请求示例**
```json
{
    "ids": [1, 2, 3]
}
```

**响应**
```json
{
    "code": 200,
    "message": "已标记为已读",
    "data": {
        "updated_count": 3
    }
}
```

---

### 4. 标记所有通知为已读 🔒

> 需要JWT认证

**请求**
```
POST /api/notifications/read-all
Content-Type: application/json
Authorization: Bearer <token>
```

**参数**
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | ❌ | 只标记某类型的通知 |

**请求示例**
```json
{
    "type": "post_like"
}
```

**响应**
```json
{
    "code": 200,
    "message": "已全部标记为已读",
    "data": {
        "updated_count": 10
    }
}
```

---

### 5. 删除通知 🔒

> 需要JWT认证

**请求**
```
DELETE /api/notifications/{id}
Authorization: Bearer <token>
```

**响应**
```json
{
    "code": 200,
    "message": "已删除通知"
}
```

**错误响应**
```json
{
    "code": 404,
    "message": "通知不存在"
}
```
