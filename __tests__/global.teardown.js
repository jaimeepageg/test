import { test as teardown } from '@playwright/test';

teardown('teardown db', async () => {
    console.log('tearing down db');
    await fetch('http://ceremonies.local/wp-json/sc/v1/e2e/teardown', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-E2E-TOKEN': 'drGiRD1i3EbO4YI0zjEEo4TU78fN0wId'
        },
    });
});