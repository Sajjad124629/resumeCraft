import json
import logging
from odoo import http
from odoo.http import request, Response

_logger = logging.getLogger(__name__)

class CvPositionApiController(http.Controller):

    @http.route(['/api/cv/positions/delete'], type='http', auth='none', methods=['POST', 'OPTIONS'], csrf=False)
    def delete_position_from_symfony(self, **kwargs):
        headers = {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'POST, OPTIONS',
            'Access-Control-Allow-Headers': 'Content-Type, Authorization',
            'Content-Type': 'application/json',
        }
        if request.httprequest.method == 'OPTIONS':
            return Response(status=204, headers=headers)

        try:
            raw_body = request.httprequest.data
            data = json.loads(raw_body.decode('utf-8')) if raw_body else {}
        except Exception:
            data = request.httprequest.form.to_dict()

        external_id = data.get('external_id') or data.get('positionId') or data.get('id')
        api_token = data.get('api_token') or data.get('token')

        domain = []
        if external_id:
            try:
                domain.append(('external_id', '=', int(external_id)))
            except (ValueError, TypeError):
                pass
        elif api_token:
            domain.append(('api_token', '=', str(api_token).strip()))

        if not domain:
            res = {'status': 'error', 'message': 'Missing external_id or api_token.'}
            return Response(json.dumps(res), status=400, headers=headers)

        # Search position using sudo() since auth='none'
        CvPosition = request.env['cv.position'].sudo()
        positions = CvPosition.search(domain)

        if not positions and api_token:
            positions = CvPosition.search([('api_token', '=', str(api_token).strip())])

        if not positions:
            res = {'status': 'not_found', 'message': f'No position found in Odoo matching external_id={external_id} or token.'}
            return Response(json.dumps(res), status=404, headers=headers)

        count = len(positions)
        pos_names = [p.name for p in positions]
        positions.with_context(from_symfony_webhook=True).unlink()

        _logger.info("Deleted %s position(s) from Odoo via Symfony webhook: %s", count, pos_names)

        res = {
            'status': 'success',
            'message': f'Successfully deleted {count} position(s) from Odoo.',
            'deleted_count': count,
            'deleted_titles': pos_names
        }
        return Response(json.dumps(res), status=200, headers=headers)
