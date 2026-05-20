/** Small date / number formatting helpers used across screens. */

/** Parse an API date. Date-only strings are treated as local time. */
function parse(value: string): Date {
  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return new Date(`${value}T00:00:00`);
  }
  return new Date(value);
}

/** "May 20" */
export function formatDateShort(value: string | null | undefined): string {
  if (!value) return '—';
  return parse(value).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

/** "Wed, May 20" */
export function formatDateLong(value: string | null | undefined): string {
  if (!value) return '—';
  return parse(value).toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  });
}

/** "Wednesday, May 20" */
export function formatDateFull(value: string | null | undefined): string {
  if (!value) return '—';
  return parse(value).toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
  });
}

/** "8:30 AM" */
export function formatTime(value: string | null | undefined): string {
  if (!value) return '—';
  return parse(value).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
}

/** Currency, e.g. "$1,250.00" */
export function formatMoney(value: number | null | undefined): string {
  const amount = value ?? 0;
  return `$${amount.toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

/** Minutes as a clock value, e.g. 495 -> "8:15" */
export function formatHoursMinutes(totalMinutes: number): string {
  const minutes = Math.max(0, Math.round(totalMinutes));
  const hours = Math.floor(minutes / 60);
  const rest = minutes % 60;
  return `${hours}:${String(rest).padStart(2, '0')}`;
}

/** Minutes as a readable duration, e.g. 495 -> "8h 15m" */
export function formatDuration(totalMinutes: number): string {
  const minutes = Math.max(0, Math.round(totalMinutes));
  const hours = Math.floor(minutes / 60);
  const rest = minutes % 60;
  if (hours === 0) return `${rest}m`;
  if (rest === 0) return `${hours}h`;
  return `${hours}h ${rest}m`;
}

/** Whole minutes elapsed between an ISO timestamp and now. */
export function minutesSince(value: string | null | undefined): number {
  if (!value) return 0;
  const diff = (Date.now() - parse(value).getTime()) / 60000;
  return Math.max(0, Math.floor(diff));
}
