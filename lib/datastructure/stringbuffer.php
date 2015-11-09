<?HH
/**
 * @author Rick Mac Gillis
 *
 * Implementation of a string buffer
 * Learn more @link http://docs.oracle.com/javase/1.5.0/docs/api/java/lang/StringBuffer.html
 */

namespace HackFastAlgos\DataStructure;

class StringBuffer
{
	private array<string> $bufferData = [];

	public function append(string $string)
	{
		$this->bufferData[] = $string;
	}

	public function __toString()
	{
		return implode('', $this->bufferData);
	}
}
