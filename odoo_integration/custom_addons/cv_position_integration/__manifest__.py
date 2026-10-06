{
    'name': 'CV Positions & Aggregated Results Integration',
    'version': '1.0.0',
    'category': 'Human Resources',
    'summary': 'View imported positions and aggregated candidate metrics from Course Project CV App',
    'description': """
        External Odoo Application acting as a read-only viewer for positions
        and aggregated attribute results (averages, min/max, popular values)
        imported via API Token from the Course Project.
    """,
    'author': 'Course Project Integration',
    'depends': ['base'],
    'data': [
        'security/ir.model.access.csv',
        'data/cv_attribute_data.xml',
        'wizard/import_position_wizard_views.xml',
        'wizard/connection_settings_wizard_views.xml',
        'views/cv_position_views.xml',
        'views/menus.xml',
    ],
    'installable': True,
    'application': True,
    'auto_install': False,
    'license': 'LGPL-3',
}
