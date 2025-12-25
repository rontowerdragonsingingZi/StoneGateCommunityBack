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
| type | string | ❌ | 消息类型：text/image/file/system，默认text |

**type 类型说明**
| 值 | 说明 | content 内容 |
|------|------|------|
| text | 纯文本消息 | 文本内容 |
| image | 图片消息 | 图片URL |
| file | 文件消息 | 文件URL |
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
| type | string | ❌ | 消息类型：text/image/file/system，默认text |

**type 类型说明**
| 值 | 说明 | content 内容 |
|------|------|------|
| text | 纯文本消息 | 文本内容 |
| image | 图片消息 | 图片URL |
| file | 文件消息 | 文件URL |
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

