import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 10  },
    { duration: '1m',  target: 50  },
    { duration: '30s', target: 100 },
    { duration: '30s', target: 0   },
  ],
  thresholds: {
    http_req_duration: ['p(95)<200'],
    http_req_failed:   ['rate<0.01'],
  },
  cloud: {
    distribution: {
      'amazon:fr:paris': { loadZone: 'amazon:fr:paris', percent: 100 },
    },
  },
};

// Fallback si BASE_URL n'est pas défini
const BASE_URL = __ENV.BASE_URL || 'https://demo-nginx.dev.fabdevlab.fr';

export function setup() {
  const loginPage = http.get(`${BASE_URL}/login`);
  check(loginPage, { 'login page accessible': (r) => r.status === 200 });

  const csrfToken = loginPage.html().find('input[name="_csrf_token"]').attr('value');

  const res = http.post(`${BASE_URL}/login`, {
    _username: __ENV.K6_USERNAME || '',
    _password: __ENV.K6_PASSWORD || '',
    _csrf_token: csrfToken,
  }, { redirects: 5 });

  const sessionCookie = res.cookies['PHPSESSID']
    ? res.cookies['PHPSESSID'][0].value
    : null;

  console.log(`Session: ${sessionCookie ? 'OK' : 'ECHEC'}`);
  return { sessionCookie };
}

export default function (data) {
  const params = {
    headers: { 'Cookie': `PHPSESSID=${data.sessionCookie}` },
  };

  let res = http.get(`${BASE_URL}/`);
  check(res, {
    'homepage 200': (r) => r.status === 200,
    'homepage < 200ms': (r) => r.timings.duration < 200,
  });

  sleep(1);

  res = http.get(`${BASE_URL}/product`, params);
  check(res, {
    'catalogue 200': (r) => r.status === 200,
    'catalogue < 200ms': (r) => r.timings.duration < 200,
  });

  sleep(1);
}