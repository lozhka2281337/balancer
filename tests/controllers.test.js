// Test file for controllers

const request = require('supertest');
const app = require('../app');

describe('Controllers', () => {
    test('GET /api/example', async () => {
        const response = await request(app).get('/api/example');
        expect(response.statusCode).toBe(200);
    });
});