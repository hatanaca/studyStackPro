import { ENDPOINTS } from '../endpoints'

describe('ENDPOINTS', () => {
  describe('auth endpoints', () => {
    it('has correct login endpoint', () => {
      expect(ENDPOINTS.auth.login).toBe('/auth/login')
    })

    it('has correct register endpoint', () => {
      expect(ENDPOINTS.auth.register).toBe('/auth/register')
    })

    it('has correct logout endpoint', () => {
      expect(ENDPOINTS.auth.logout).toBe('/auth/logout')
    })

    it('has correct me endpoint', () => {
      expect(ENDPOINTS.auth.me).toBe('/auth/me')
    })

    it('has correct changePassword endpoint', () => {
      expect(ENDPOINTS.auth.changePassword).toBe('/auth/change-password')
    })

    it('oauthRedirect builds correct URL', () => {
      expect(ENDPOINTS.auth.oauthRedirect('google')).toBe('/auth/google')
      expect(ENDPOINTS.auth.oauthRedirect('discord')).toBe('/auth/discord')
    })

    it('oauthCallback builds correct URL', () => {
      expect(ENDPOINTS.auth.oauthCallback('google')).toBe('/auth/google/callback')
    })
  })

  describe('sessions endpoints', () => {
    it('has correct list endpoint', () => {
      expect(ENDPOINTS.sessions.list).toBe('/study-sessions')
    })

    it('has correct active endpoint', () => {
      expect(ENDPOINTS.sessions.active).toBe('/study-sessions/active')
    })

    it('has correct start endpoint', () => {
      expect(ENDPOINTS.sessions.start).toBe('/study-sessions/start')
    })

    it('one builds correct URL', () => {
      expect(ENDPOINTS.sessions.one('session-1')).toBe('/study-sessions/session-1')
    })

    it('end builds correct URL', () => {
      expect(ENDPOINTS.sessions.end('session-1')).toBe('/study-sessions/session-1/end')
    })
  })

  describe('technologies endpoints', () => {
    it('has correct list endpoint', () => {
      expect(ENDPOINTS.technologies.list).toBe('/technologies')
    })

    it('has correct search endpoint', () => {
      expect(ENDPOINTS.technologies.search).toBe('/technologies/search')
    })

    it('one builds correct URL', () => {
      expect(ENDPOINTS.technologies.one('tech-1')).toBe('/technologies/tech-1')
    })
  })

  describe('analytics endpoints', () => {
    it('has correct dashboard endpoint', () => {
      expect(ENDPOINTS.analytics.dashboard).toBe('/analytics/dashboard')
    })

    it('has correct userMetrics endpoint', () => {
      expect(ENDPOINTS.analytics.userMetrics).toBe('/analytics/user-metrics')
    })

    it('has correct techStats endpoint', () => {
      expect(ENDPOINTS.analytics.techStats).toBe('/analytics/tech-stats')
    })

    it('has correct timeSeries endpoint', () => {
      expect(ENDPOINTS.analytics.timeSeries).toBe('/analytics/time-series')
    })

    it('has correct heatmap endpoint', () => {
      expect(ENDPOINTS.analytics.heatmap).toBe('/analytics/heatmap')
    })

    it('has correct export endpoint', () => {
      expect(ENDPOINTS.analytics.export).toBe('/analytics/export')
    })
  })

  describe('youtube endpoints', () => {
    it('has correct search endpoint', () => {
      expect(ENDPOINTS.youtube.search).toBe('/youtube/search')
    })

    it('has correct videos endpoint', () => {
      expect(ENDPOINTS.youtube.videos).toBe('/youtube/videos')
    })

    it('has correct playlists endpoint', () => {
      expect(ENDPOINTS.youtube.playlists).toBe('/youtube/playlists')
    })
  })

  describe('linkedin endpoints', () => {
    it('has correct status endpoint', () => {
      expect(ENDPOINTS.linkedin.status).toBe('/linkedin/status')
    })

    it('has correct share endpoint', () => {
      expect(ENDPOINTS.linkedin.share).toBe('/linkedin/share')
    })

    it('has correct disconnect endpoint', () => {
      expect(ENDPOINTS.linkedin.disconnect).toBe('/linkedin/disconnect')
    })
  })

  describe('code endpoints', () => {
    it('has correct execute endpoint', () => {
      expect(ENDPOINTS.code.execute).toBe('/code/execute')
    })

    it('has correct languages endpoint', () => {
      expect(ENDPOINTS.code.languages).toBe('/code/languages')
    })
  })
})
