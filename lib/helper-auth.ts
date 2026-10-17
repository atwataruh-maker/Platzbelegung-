// This allowlist is checked against identity headers supplied by Sites dispatch,
// never against email or role values sent in a request body.
export function canManageHelpers(headers: Headers) {
  return Boolean(headers.get('oai-authenticated-user-id')) && headers.get('oai-authenticated-user-email')?.toLowerCase() === 'atwataruh@googlemail.com';
}
export function isSameOriginMutation(request: Request) {
  const origin = request.headers.get('origin');
  return (!origin || origin === new URL(request.url).origin) && request.headers.get('content-type')?.split(';')[0] === 'application/json';
}
