import type { Task } from '@/types/task'
import type { TaskStatus } from '@/types/task-status'

/** One column of `GET board`: a status with its tasks in board order. */
export interface BoardColumn {
  status: TaskStatus
  tasks: Task[]
}

export interface BoardParams {
  project_id?: number
  assignee_id?: number
}

/**
 * What the host select inputs bind. They hand back the whole option object
 * rather than an id, so every picker works on this shape.
 */
export interface SelectOption {
  id: number
  label: string
}
