# Future Gadget Lab API 文档

Base URL: `http://localhost:8012/api`

> El Psy Kongroo - 命运石之门社区后端接口

---

## Labmem管理 (Users)

### 1. 获取所有Labmem

**请求**
```
GET /api/users
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

### 2. 注册新Labmem

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

### 3. 获取单个Labmem信息

**请求**
```
GET /api/users/{id}
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

### 4. 更新Labmem信息

**请求**
```
PUT /api/users/{id}
Content-Type: application/json
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

### 5. 移除Labmem

**请求**
```
DELETE /api/users/{id}
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

### 6. Labmem登录

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

**错误响应（D-Mail密钥错误）**
```json
{
    "code": 401,
    "message": "认证失败，D-Mail密钥不匹配",
    "data": null
}
```

---

### 7. 检查Labmem代号是否可用

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

## 图床上传（Cloudflare R2）

POST /api/upload-image

- Content-Type: multipart/form-data
- 字段：
  - file: 图片文件（必填，jpg/jpeg/png/gif/webp/avif，≤10MB）
  - folder: 目标目录（可选，默认 uploads/images）

示例

curl -i -X POST https://api.mahoer.space/api/upload-image \
  -F "file=@/path/to/image.jpg" \
  -F "folder=avatars"

成功响应

{
  "code": 201,
  "message": "金属乌帕已穿越至R2世界线",
  "data": {
    "key": "avatars/2025/12/23/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.jpg",
    "mime": "image/jpeg",
    "size": 123456,
    "url": "https://<你的公共域名>/avatars/2025/12/23/xxxx.jpg" // 若设置了 R2_PUBLIC_BASE_URL
  }
}

---

## 列出图片

GET /api/images?folder=avatars&per_page=50&page=1&deep=1

响应

{
  "code": 200,
  "message": "El Psy Kongroo",
  "data": {
    "total": 123,
    "page": 1,
    "per_page": 50,
    "items": [
      { "key": "avatars/2025/12/23/xxx.jpg", "url": "https://<你的公共域名>/avatars/2025/12/23/xxx.jpg" },
      { "key": "uploads/images/2025/12/23/yyy.png", "url": null }
    ]
  }
}

说明：当 .env 设置了 R2_PUBLIC_BASE_URL 时会返回可直连的 url；否则 url 为 null，可使用“生成临时链接”接口。

---

## 生成临时访问链接（用于私有桶）

GET /api/images/presign?key=avatars/2025/12/23/xxx.jpg&expires=900

响应

{
  "code": 200,
  "message": "El Psy Kongroo",
  "data": { "url": "https://签名后的临时地址" }
}

POST /api/upload-image

- Content-Type: multipart/form-data
- 字段：
  - file: 图片文件（必填，jpg/jpeg/png/gif/webp/avif，≤10MB）
  - folder: 目标目录（可选，默认 uploads/images）

示例

curl -i -X POST https://api.mahoer.space/api/upload-image \
  -F "file=@/path/to/image.jpg" \
  -F "folder=avatars"

成功响应

{
  "code": 201,
  "message": "金属乌帕已穿越至R2世界线",
  "data": {
    "key": "avatars/2025/12/23/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.jpg",
    "mime": "image/jpeg",
    "size": 123456,
    "url": "https://<你的公共域名>/avatars/2025/12/23/xxxx.jpg" // 若设置了 R2_PUBLIC_BASE_URL
  }
}

| 状态码 | 说明 |
|--------|------|
| 200 | El Psy Kongroo - 操作成功 |
| 201 | Labmem注册完成 |
| 401 | D-Mail密钥不匹配 |
| 404 | Labmem不存在于此世界线 |
| 409 | 世界线冲突（代号已存在）|
| 422 | 参数错误（无效的输入）|
| 500 | 世界线异常（服务器错误）|
