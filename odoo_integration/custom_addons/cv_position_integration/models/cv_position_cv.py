from odoo import models, fields

class CvPositionCv(models.Model):
    _name = 'cv.position.cv'
    _description = 'Position Submitted CV'
    _order = 'id desc'

    position_id = fields.Many2one('cv.position', string='Position', ondelete='cascade', required=True, readonly=True)
    external_cv_id = fields.Integer(string='CV ID', readonly=True)
    candidate_name = fields.Char(string='Candidate Name', required=True, readonly=True)
    status = fields.Char(string='Status', readonly=True)
    likes = fields.Integer(string='Likes', readonly=True)
    created_at = fields.Char(string='Submitted At', readonly=True)
