/**
 * A user attached to a project, as `ProjectMemberResource` renders it.
 *
 * `user_id` points at a host user without a foreign key, so a member who has
 * left the company still renders here.
 */
export interface ProjectMember {
  id: number
  company_id: number
  project_id: number
  user_id: number
  /** Minor units per hour for this member on this project. */
  rate: number | null
  created_at: string | null
  updated_at: string | null
}

export interface ProjectMemberInput {
  user_id: number
  rate: number | null
}
