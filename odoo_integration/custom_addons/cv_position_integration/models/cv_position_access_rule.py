from odoo import models, fields

class CvPositionAccessRule(models.Model):
    _name = 'cv.position.access.rule'
    _description = 'Position Access Rule'
    _order = 'id asc'

    position_id = fields.Many2one(
        'cv.position',
        string='Position',
        required=True,
        ondelete='cascade'
    )
    attribute_id = fields.Many2one(
        'cv.attribute',
        string='Attribute',
        required=True
    )
    operator = fields.Selection([
        ('=', '='),
        ('>', '>'),
        ('<', '<'),
    ], string='Operator', default='=', required=True)
    value = fields.Char(string='Value', required=True)
