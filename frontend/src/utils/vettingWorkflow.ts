import type { Application } from '@/api/applications'

export function canStartPoliceVetting(application: Application, userId?: number | null): boolean {
  const statusCode = application.status?.code

  return application.assigned_police_officer?.id === userId &&
    !application.police_vetting_completed_at &&
    (statusCode === 'pending' || statusCode === 'police_vetting')
}

export function canStartNisVetting(application: Application, userId?: number | null): boolean {
  const statusCode = application.status?.code

  return application.assigned_nis_officer?.id === userId &&
    !application.nis_vetting_completed_at &&
    statusCode === 'nis_vetting'
}
