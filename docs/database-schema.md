# ProjectPulse Database Schema

## Entity Relationship Summary

```text
users (1) ───< (0..N) clients [created_by]
users (1) ───< (0..N) projects [created_by]
users (1) ───< (0..N) tasks [assignee_id / created_by]
users (1) ───< (0..N) time_logs [user_id]
users (1) ───< (0..N) progress_notes [user_id]
users (1) ───< (0..N) task_comments [user_id]
users (1) ───< (0..N) notifications [user_id]
users (1) ───< (0..N) ai_task_generations [requested_by]

clients (1) ───< (0..N) projects [client_id]

projects (1) ───< (0..N) tasks [project_id]

tasks (1) ───< (0..N) time_logs [task_id]
tasks (1) ───< (0..N) progress_notes [task_id]
tasks (1) ───< (0..N) task_comments [task_id]
```

---

## Detailed Table Definitions

### 1. `users`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `bigint` | PK, Auto Increment | User ID |
| `name` | `varchar(255)` | NOT NULL | Full name |
| `email` | `varchar(255)` | UNIQUE, NOT NULL, Index | Email address |
| `password` | `varchar(255)` | NOT NULL | Hashed password |
| `role` | `enum('admin', 'member')` | NOT NULL, Index | System role |
| `job_title` | `varchar(255)` | NOT NULL | Job title (e.g., Senior Backend Engineer) |
| `avatar_url` | `varchar(255)` | NULLABLE | Avatar image path |
| `is_active` | `boolean` | DEFAULT true | Account status flag |
| `email_verified_at` | `timestamp` | NULLABLE | Verification timestamp |
| `created_at` | `timestamp` | NOT NULL | Creation timestamp |
| `updated_at` | `timestamp` | NOT NULL | Update timestamp |

### 2. `clients`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `bigint` | PK, Auto Increment | Client ID |
| `name` | `varchar(255)` | NOT NULL | Contact name |
| `company` | `varchar(255)` | NOT NULL | Company name |
| `email` | `varchar(255)` | NULLABLE | Email address |
| `phone` | `varchar(50)` | NULLABLE | Phone number |
| `address` | `text` | NULLABLE | Office address |
| `notes` | `text` | NULLABLE | Additional notes |
| `created_by` | `bigint` | FK -> users.id | Creator ID |
| `created_at` | `timestamp` | NOT NULL | Creation timestamp |
| `updated_at` | `timestamp` | NOT NULL | Update timestamp |
| `deleted_at` | `timestamp` | NULLABLE | Soft delete timestamp |

### 3. `projects`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `bigint` | PK, Auto Increment | Project ID |
| `client_id` | `bigint` | FK -> clients.id, Index | Client ID |
| `name` | `varchar(255)` | NOT NULL | Project title |
| `description` | `text` | NULLABLE | Detailed summary |
| `client_brief` | `text` | NULLABLE | Client requirements brief |
| `start_date` | `date` | NULLABLE | Target start date |
| `deadline` | `date` | NOT NULL, Index | Target completion date |
| `status` | `enum('draft', 'active', 'on_hold', 'completed', 'cancelled')` | NOT NULL, Index | Project lifecycle status |
| `created_by` | `bigint` | FK -> users.id | Project creator |
| `created_at` | `timestamp` | NOT NULL | Creation timestamp |
| `updated_at` | `timestamp` | NOT NULL | Update timestamp |
| `deleted_at` | `timestamp` | NULLABLE | Soft delete timestamp |

### 4. `tasks`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `bigint` | PK, Auto Increment | Task ID |
| `project_id` | `bigint` | FK -> projects.id, Index | Project ID |
| `title` | `varchar(255)` | NOT NULL | Task title |
| `description` | `text` | NULLABLE | Description and acceptance criteria |
| `category` | `enum('frontend', 'backend', 'design', 'qa', 'devops', 'management', 'other')` | NOT NULL, Index | Task functional area |
| `assignee_id` | `bigint` | FK -> users.id, NULLABLE, Index | Assigned member |
| `priority` | `enum('low', 'medium', 'high', 'urgent')` | NOT NULL | Task priority |
| `status` | `enum('todo', 'in_progress', 'review', 'done')` | NOT NULL, Index | Task workflow state |
| `estimated_hours` | `decimal(5,2)` | NULLABLE | Estimated work hours |
| `start_date` | `date` | NULLABLE | Work start date |
| `deadline` | `date` | NULLABLE, Index | Task deadline |
| `completed_at` | `timestamp` | NULLABLE | Task completion timestamp |
| `created_by` | `bigint` | FK -> users.id | Task creator |
| `source` | `enum('manual', 'ai')` | NOT NULL DEFAULT 'manual' | Source of task |
| `created_at` | `timestamp` | NOT NULL | Creation timestamp |
| `updated_at` | `timestamp` | NOT NULL | Update timestamp |
| `deleted_at` | `timestamp` | NULLABLE | Soft delete timestamp |

### 5. `time_logs`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `bigint` | PK, Auto Increment | Time log ID |
| `task_id` | `bigint` | FK -> tasks.id, Index | Associated task |
| `user_id` | `bigint` | FK -> users.id, Index | Member logging time |
| `work_date` | `date` | NOT NULL | Date work occurred |
| `duration_minutes` | `integer` | NOT NULL | Work duration in minutes |
| `note` | `text` | NULLABLE | Activity notes |
| `created_at` | `timestamp` | NOT NULL | Creation timestamp |
| `updated_at` | `timestamp` | NOT NULL | Update timestamp |

### 6. `progress_notes`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `bigint` | PK, Auto Increment | Progress note ID |
| `task_id` | `bigint` | FK -> tasks.id, Index | Associated task |
| `user_id` | `bigint` | FK -> users.id | Member posting note |
| `note` | `text` | NOT NULL | Detailed update note |
| `status_snapshot` | `varchar(50)` | NULLABLE | Status when note was submitted |
| `created_at` | `timestamp` | NOT NULL | Creation timestamp |
| `updated_at` | `timestamp` | NOT NULL | Update timestamp |

### 7. `task_comments`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `bigint` | PK, Auto Increment | Comment ID |
| `task_id` | `bigint` | FK -> tasks.id, Index | Associated task |
| `user_id` | `bigint` | FK -> users.id | Comment author |
| `body` | `text` | NOT NULL | Comment text |
| `edited_at` | `timestamp` | NULLABLE | Edit timestamp |
| `created_at` | `timestamp` | NOT NULL | Creation timestamp |
| `updated_at` | `timestamp` | NOT NULL | Update timestamp |
| `deleted_at` | `timestamp` | NULLABLE | Soft delete timestamp |

### 8. `notifications`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `uuid` | PK | Notification UUID |
| `user_id` | `bigint` | FK -> users.id, Index | Target user ID |
| `type` | `varchar(255)` | NOT NULL | Notification type class |
| `title` | `varchar(255)` | NOT NULL | Notification title |
| `message` | `text` | NOT NULL | Notification body |
| `data` | `jsonb` | NULLABLE | Payloads (e.g. task_id) |
| `read_at` | `timestamp` | NULLABLE, Index | Read timestamp |
| `created_at` | `timestamp` | NOT NULL | Creation timestamp |
| `updated_at` | `timestamp` | NOT NULL | Update timestamp |

### 9. `ai_task_generations`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `bigint` | PK, Auto Increment | Generation ID |
| `project_id` | `bigint` | FK -> projects.id, NULLABLE | Associated project |
| `requested_by` | `bigint` | FK -> users.id | Admin user ID |
| `provider` | `varchar(50)` | NOT NULL | OpenAI / Gemini |
| `model` | `varchar(100)` | NOT NULL | Model identifier |
| `brief_hash` | `varchar(64)` | NOT NULL | SHA256 hash of client brief |
| `request_payload` | `jsonb` | NULLABLE | Sanitized request payload |
| `response_payload` | `jsonb` | NULLABLE | Sanitized response payload |
| `status` | `enum('pending', 'success', 'failed', 'timeout')` | NOT NULL | Generation status |
| `error_code` | `varchar(100)` | NULLABLE | Error identifier |
| `error_message` | `text` | NULLABLE | Error details |
| `latency_ms` | `integer` | NULLABLE | API execution time in ms |
| `created_at` | `timestamp` | NOT NULL | Creation timestamp |
| `updated_at` | `timestamp` | NOT NULL | Update timestamp |
