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

const BASE_URL = __ENV.BASE_URL;

export function setup() {
  // Récupération du CSRF token sur la page login
  const loginPage = http.get(`${BASE_URL}/login`);
  check(loginPage, { 'login page accessible': (r) => r.status === 200 });

  // Extraction du token CSRF depuis le formulaire Symfony
  const csrfToken = loginPage.html().find('input[name="_csrf_token"]').attr('value');
  console.log(`CSRF token: ${csrfToken ? 'OK' : 'ECHEC'}`);

  // Login via formulaire Symfony → cookie de session
  const res = http.post(`${BASE_URL}/login`, {
    _username: __ENV.K6_USERNAME,
    _password: __ENV.K6_PASSWORD,
    _csrf_token: csrfToken,
  }, {
    redirects: 5,
  });

  console.log(`Login status: ${res.status}`);

  // Récupération du cookie de session
  const sessionCookie = res.cookies['PHPSESSID'] 
    ? res.cookies['PHPSESSID'][0].value 
    : null;
  console.log(`Session cookie: ${sessionCookie ? 'OK' : 'ECHEC'}`);

  return { sessionCookie };
}

export default function (data) {
  const params = {
    headers: {
      'Cookie': `PHPSESSID=${data.sessionCookie}`,
    },
  };

  // Test homepage — accessible sans connexion
  let res = http.get(`${BASE_URL}/`);
  check(res, {
    'homepage status 200':     (r) => r.status === 200,
    'homepage p95 < 200ms':    (r) => r.timings.duration < 200,
  });

  sleep(1);

  // Test catalogue — accessible uniquement si connecté
  res = http.get(`${BASE_URL}/product`, params);
  check(res, {
    'catalogue status 200':    (r) => r.status === 200,
    'catalogue p95 < 200ms':   (r) => r.timings.duration < 200,
  });

  sleep(1);
}