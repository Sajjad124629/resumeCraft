import os
import json
import urllib.request
import urllib.error
import ssl
from odoo import models, fields, api, _
from odoo.exceptions import UserError

class ConnectionSettingsWizard(models.TransientModel):
    _name = 'cv.connection.settings.wizard'
    _description = 'Course Project Connection Settings'

    def _default_symfony_url(self):
        param_url = self.env['ir.config_parameter'].sudo().get_param('cv.symfony_base_url')
        if param_url:
            return param_url.strip().rstrip('/')
        env_url = os.environ.get('SYMFONY_BASE_URL')
        if env_url:
            return env_url.strip().rstrip('/')
        return 'http://172.17.0.1:8000'

    symfony_url = fields.Char(
        string='Course Project URL',
        default=_default_symfony_url,
        required=True,
        help='URL of your Symfony app (e.g. https://your-domain.com or http://172.17.0.1:8000 for local docker)'
    )

    def action_save_and_test(self):
        self.ensure_one()
        url = (self.symfony_url or '').strip().rstrip('/')
        if not url:
            raise UserError(_('Please enter a valid URL.'))

        # Test the connection by requesting /api/positions/attributes
        test_endpoint = f"{url}/api/positions/attributes"
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        opener = urllib.request.build_opener(urllib.request.HTTPSHandler(context=ctx))
        req = urllib.request.Request(
            test_endpoint,
            headers={'User-Agent': 'Odoo-Integration/1.0', 'Accept': 'application/json'}
        )

        try:
            with opener.open(req, timeout=10) as response:
                if response.status in (200, 201):
                    self.env['ir.config_parameter'].sudo().set_param('cv.symfony_base_url', url)
                    return {
                        'type': 'ir.actions.client',
                        'tag': 'display_notification',
                        'params': {
                            'title': _('Connection Successful!'),
                            'message': _('Successfully connected to Course Project at %s. URL saved!') % url,
                            'sticky': False,
                            'type': 'success',
                        }
                    }
        except Exception as e:
            # Still save so user can proceed if Symfony is temporarily down or self-signed
            self.env['ir.config_parameter'].sudo().set_param('cv.symfony_base_url', url)
            raise UserError(
                _("Saved URL to '%s', but connection test returned:\n\n%s\n\n"
                  "Please check if the Symfony server is running and accessible.") % (url, str(e))
            )
