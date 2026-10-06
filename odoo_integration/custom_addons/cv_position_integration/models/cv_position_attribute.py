from odoo import models, fields

class CvPositionAttribute(models.Model):
    _name = 'cv.position.attribute'
    _description = 'Position Attribute and Aggregated Results'
    _order = 'id asc'

    position_id = fields.Many2one(
        'cv.position',
        string='Position',
        ondelete='cascade',
        required=True,
        readonly=True
    )
    attribute_title = fields.Char(string='Attribute Title', required=True, readonly=True)
    attribute_type = fields.Char(string='Type', required=True, readonly=True)
    aggregation_kind = fields.Selection([
        ('numeric', 'Numeric Metrics'),
        ('text', 'Categorical / Popular Values')
    ], string='Aggregation Kind', readonly=True)

    # Numeric aggregated results
    min_value = fields.Float(string='Min Value', readonly=True)
    max_value = fields.Float(string='Max Value', readonly=True)
    avg_value = fields.Float(string='Average', readonly=True)
    count = fields.Integer(string='Candidate Count', readonly=True)

    # Text / Categorical aggregated results
    popular_values = fields.Text(string='Popular Values', readonly=True)

    # Clean display summary
    summary = fields.Text(string='Aggregated Summary', readonly=True)
