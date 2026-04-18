/** Evento disparado ao remover um lembrete local (por tecnologia). */
export const STUDYTRACK_REMINDER_REMOVED_EVENT = 'studytrack-technology-reminder-removed'

export interface ReminderRemovedDetail {
  technologyId: string
  text: string
}

/** Remove linhas de `full` cujo trim é igual a `lineToRemove` (trim). */
export function stripNotesLinesMatching(full: string, lineToRemove: string): string {
  const rm = lineToRemove.trim()
  if (!rm) return full
  return full
    .replace(/\r\n/g, '\n')
    .split('\n')
    .filter((line) => line.trim() !== rm)
    .join('\n')
    .trim()
}
